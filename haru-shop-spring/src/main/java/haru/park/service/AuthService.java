package haru.park.service;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import haru.park.dto.AuthResponse;
import haru.park.dto.UserDto;
import haru.park.entity.User;
import haru.park.mapper.UserMapper;
import haru.park.util.JwtUtil;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.time.LocalDateTime;
import java.util.Objects;

@Service
public class AuthService {

    private static final String GOOGLE_TOKENINFO_URL = "https://oauth2.googleapis.com/tokeninfo?id_token=";
    private static final String GOOGLE_TOKEN_URL = "https://oauth2.googleapis.com/token";
    private static final String GOOGLE_AUTH_URL = "https://accounts.google.com/o/oauth2/v2/auth";
    private static final String NAVER_AUTH_URL = "https://nid.naver.com/oauth2.0/authorize";
    private static final String NAVER_TOKEN_URL = "https://nid.naver.com/oauth2.0/token";
    private static final String NAVER_USER_URL = "https://openapi.naver.com/v1/nid/me";
    private static final String KAKAO_AUTH_URL = "https://kauth.kakao.com/oauth/authorize";
    private static final String KAKAO_TOKEN_URL = "https://kauth.kakao.com/oauth/token";
    private static final String KAKAO_USER_URL = "https://kapi.kakao.com/v2/user/me";

    private final UserMapper userMapper;
    private final JwtUtil jwtUtil;
    private final RestTemplate restTemplate = new RestTemplate();
    private final ObjectMapper objectMapper = new ObjectMapper();

    @Value("${google.client-id:}")
    private String googleClientId;

    @Value("${google.client-secret:}")
    private String googleClientSecret;

    @Value("${google.redirect-uri:}")
    private String googleRedirectUri;

    @Value("${frontend.redirect-uri:}")
    private String frontendRedirectUri;

    @Value("${naver.client-id:}")
    private String naverClientId;

    @Value("${naver.client-secret:}")
    private String naverClientSecret;

    @Value("${naver.redirect-uri:}")
    private String naverRedirectUri;

    @Value("${kakao.client-id:}")
    private String kakaoClientId;

    @Value("${kakao.client-secret:}")
    private String kakaoClientSecret;

    @Value("${kakao.redirect-uri:}")
    private String kakaoRedirectUri;

    public AuthService(UserMapper userMapper, JwtUtil jwtUtil) {
        this.userMapper = userMapper;
        this.jwtUtil = jwtUtil;
    }

    /** 리다이렉트 방식: Google 로그인 페이지 URL 생성 */
    public String buildGoogleAuthUrl(String state) {
        String scope = "openid email profile";
        return GOOGLE_AUTH_URL
                + "?client_id=" + java.net.URLEncoder.encode(googleClientId, java.nio.charset.StandardCharsets.UTF_8)
                + "&redirect_uri=" + java.net.URLEncoder.encode(googleRedirectUri, java.nio.charset.StandardCharsets.UTF_8)
                + "&response_type=code"
                + "&scope=" + java.net.URLEncoder.encode(scope, java.nio.charset.StandardCharsets.UTF_8)
                + "&state=" + java.net.URLEncoder.encode(state, java.nio.charset.StandardCharsets.UTF_8)
                + "&access_type=offline";
    }

    /** authorization code로 id_token 취득 후 로그인 처리 */
    public AuthResponse loginWithGoogleCode(String code) {
        String idToken = exchangeCodeForIdToken(code);
        return loginWithGoogle(idToken);
    }

    public String getFrontendRedirectUri() {
        return frontendRedirectUri;
    }

    /** 리다이렉트 방식: 카카오 로그인 페이지 URL 생성 */
    public String buildKakaoAuthUrl(String state) {
        return KAKAO_AUTH_URL
                + "?client_id=" + java.net.URLEncoder.encode(kakaoClientId, java.nio.charset.StandardCharsets.UTF_8)
                + "&redirect_uri=" + java.net.URLEncoder.encode(kakaoRedirectUri, java.nio.charset.StandardCharsets.UTF_8)
                + "&response_type=code"
                + "&state=" + java.net.URLEncoder.encode(state, java.nio.charset.StandardCharsets.UTF_8);
    }

    /** 카카오 authorization code로 access_token 취득 후 사용자 정보로 로그인 처리 */
    public AuthResponse loginWithKakaoCode(String code) {
        String accessToken = exchangeKakaoCodeForAccessToken(code);
        return loginWithKakaoAccessToken(accessToken);
    }

    private String exchangeKakaoCodeForAccessToken(String code) {
        String body = "grant_type=authorization_code"
                + "&client_id=" + java.net.URLEncoder.encode(kakaoClientId, java.nio.charset.StandardCharsets.UTF_8)
                + "&redirect_uri=" + java.net.URLEncoder.encode(kakaoRedirectUri, java.nio.charset.StandardCharsets.UTF_8)
                + "&code=" + java.net.URLEncoder.encode(code, java.nio.charset.StandardCharsets.UTF_8);
        if (kakaoClientSecret != null && !kakaoClientSecret.isEmpty()) {
            body += "&client_secret=" + java.net.URLEncoder.encode(kakaoClientSecret, java.nio.charset.StandardCharsets.UTF_8);
        }
        org.springframework.http.HttpHeaders headers = new org.springframework.http.HttpHeaders();
        headers.setContentType(org.springframework.http.MediaType.APPLICATION_FORM_URLENCODED);
        org.springframework.http.HttpEntity<String> request = new org.springframework.http.HttpEntity<>(body, headers);
        ResponseEntity<String> response = restTemplate.postForEntity(KAKAO_TOKEN_URL, request, String.class);
        if (!response.getStatusCode().is2xxSuccessful() || response.getBody() == null) {
            throw new IllegalArgumentException("Failed to exchange Kakao code for token");
        }
        try {
            JsonNode node = objectMapper.readTree(response.getBody());
            if (node.has("error")) {
                throw new IllegalArgumentException("Kakao token exchange failed: " + node.get("error").asText());
            }
            JsonNode tokenNode = node.has("access_token") ? node.get("access_token") : node.get("accessToken");
            if (tokenNode == null) {
                throw new IllegalArgumentException("No access_token in Kakao response");
            }
            return tokenNode.asText();
        } catch (Exception e) {
            if (e instanceof IllegalArgumentException) throw (IllegalArgumentException) e;
            throw new IllegalArgumentException("Invalid Kakao token response", e);
        }
    }

    private AuthResponse loginWithKakaoAccessToken(String accessToken) {
        org.springframework.http.HttpHeaders headers = new org.springframework.http.HttpHeaders();
        headers.setBearerAuth(accessToken);
        org.springframework.http.HttpEntity<Void> request = new org.springframework.http.HttpEntity<>(headers);
        ResponseEntity<String> response = restTemplate.exchange(
                KAKAO_USER_URL,
                org.springframework.http.HttpMethod.GET,
                request,
                String.class
        );
        if (!response.getStatusCode().is2xxSuccessful() || response.getBody() == null) {
            throw new IllegalArgumentException("Failed to get Kakao user info");
        }
        try {
            JsonNode root = objectMapper.readTree(response.getBody());
            long kakaoId = root.has("id") ? root.get("id").asLong() : 0L;
            String email;
            String name;
            String picture = null;
            JsonNode kakaoAccount = root.get("kakao_account");
            if (kakaoAccount != null) {
                email = kakaoAccount.has("email") && !kakaoAccount.get("email").isNull()
                        ? kakaoAccount.get("email").asText()
                        : ("kakao_" + kakaoId + "@kakao.oauth");
                JsonNode profile = kakaoAccount.has("profile") ? kakaoAccount.get("profile") : null;
                name = (profile != null && profile.has("nickname")) ? profile.get("nickname").asText() : email;
                if (profile != null && profile.has("profile_image_url")) {
                    picture = profile.get("profile_image_url").asText();
                }
            } else {
                email = "kakao_" + kakaoId + "@kakao.oauth";
                name = email;
            }
            if (name == null) name = email;

            User user = userMapper.selectByEmail(email);
            if (user == null) {
                user = new User();
                user.setEmail(email);
                user.setName(name);
                user.setPicture(picture);
                user.setProvider("kakao");
                user.setRole("user");
                user.setCreatedAt(LocalDateTime.now());
                userMapper.insert(user);
            } else {
                user.setName(name);
                user.setPicture(picture);
                userMapper.update(user);
            }

            String token = jwtUtil.generateToken(user);
            UserDto userDto = new UserDto(user.getId(), user.getEmail(), user.getName(), user.getPicture(), user.getRole());
            return new AuthResponse(token, userDto);
        } catch (Exception e) {
            if (e instanceof IllegalArgumentException) throw (IllegalArgumentException) e;
            throw new IllegalArgumentException("Invalid Kakao user response", e);
        }
    }

    /** 리다이렉트 방식: 네이버 로그인 페이지 URL 생성 */
    public String buildNaverAuthUrl(String state) {
        return NAVER_AUTH_URL
                + "?client_id=" + java.net.URLEncoder.encode(naverClientId, java.nio.charset.StandardCharsets.UTF_8)
                + "&redirect_uri=" + java.net.URLEncoder.encode(naverRedirectUri, java.nio.charset.StandardCharsets.UTF_8)
                + "&response_type=code"
                + "&state=" + java.net.URLEncoder.encode(state, java.nio.charset.StandardCharsets.UTF_8);
    }

    /** 네이버 authorization code로 access_token 취득 후 사용자 정보로 로그인 처리 */
    public AuthResponse loginWithNaverCode(String code) {
        String accessToken = exchangeNaverCodeForAccessToken(code);
        return loginWithNaverAccessToken(accessToken);
    }

    private String exchangeNaverCodeForAccessToken(String code) {
        String body = "grant_type=authorization_code"
                + "&client_id=" + java.net.URLEncoder.encode(naverClientId, java.nio.charset.StandardCharsets.UTF_8)
                + "&client_secret=" + java.net.URLEncoder.encode(naverClientSecret, java.nio.charset.StandardCharsets.UTF_8)
                + "&code=" + java.net.URLEncoder.encode(code, java.nio.charset.StandardCharsets.UTF_8)
                + "&state=";
        org.springframework.http.HttpHeaders headers = new org.springframework.http.HttpHeaders();
        headers.setContentType(org.springframework.http.MediaType.APPLICATION_FORM_URLENCODED);
        org.springframework.http.HttpEntity<String> request = new org.springframework.http.HttpEntity<>(body, headers);
        ResponseEntity<String> response = restTemplate.postForEntity(NAVER_TOKEN_URL, request, String.class);
        if (!response.getStatusCode().is2xxSuccessful() || response.getBody() == null) {
            throw new IllegalArgumentException("Failed to exchange Naver code for token");
        }
        try {
            JsonNode node = objectMapper.readTree(response.getBody());
            if (node.has("error")) {
                throw new IllegalArgumentException("Naver token exchange failed: " + node.get("error").asText());
            }
            JsonNode tokenNode = node.has("access_token") ? node.get("access_token") : node.get("accessToken");
            if (tokenNode == null) {
                throw new IllegalArgumentException("No access_token in Naver response");
            }
            return tokenNode.asText();
        } catch (Exception e) {
            if (e instanceof IllegalArgumentException) throw (IllegalArgumentException) e;
            throw new IllegalArgumentException("Invalid Naver token response", e);
        }
    }

    private AuthResponse loginWithNaverAccessToken(String accessToken) {
        org.springframework.http.HttpHeaders headers = new org.springframework.http.HttpHeaders();
        headers.setBearerAuth(accessToken);
        org.springframework.http.HttpEntity<Void> request = new org.springframework.http.HttpEntity<>(headers);
        ResponseEntity<String> response = restTemplate.exchange(
                NAVER_USER_URL,
                org.springframework.http.HttpMethod.GET,
                request,
                String.class
        );
        if (!response.getStatusCode().is2xxSuccessful() || response.getBody() == null) {
            throw new IllegalArgumentException("Failed to get Naver user info");
        }
        try {
            JsonNode root = objectMapper.readTree(response.getBody());
            if (!"00".equals(root.path("resultcode").asText(null))) {
                throw new IllegalArgumentException("Naver API error: " + root.path("message").asText(""));
            }
            JsonNode res = root.get("response");
            if (res == null) {
                throw new IllegalArgumentException("No response in Naver user info");
            }
            String naverId = res.has("id") ? res.get("id").asText() : "";
            String email = res.has("email") ? res.get("email").asText() : ("naver_" + naverId + "@naver.oauth");
            String name = res.has("name") ? res.get("name").asText() : email;
            String picture = res.has("profile_image") ? res.get("profile_image").asText() : null;

            User user = userMapper.selectByEmail(email);
            if (user == null) {
                user = new User();
                user.setEmail(email);
                user.setName(name);
                user.setPicture(picture);
                user.setProvider("naver");
                user.setRole("user");
                user.setCreatedAt(LocalDateTime.now());
                userMapper.insert(user);
            } else {
                user.setName(name);
                user.setPicture(picture);
                userMapper.update(user);
            }

            String token = jwtUtil.generateToken(user);
            UserDto userDto = new UserDto(user.getId(), user.getEmail(), user.getName(), user.getPicture(), user.getRole());
            return new AuthResponse(token, userDto);
        } catch (Exception e) {
            if (e instanceof IllegalArgumentException) throw (IllegalArgumentException) e;
            throw new IllegalArgumentException("Invalid Naver user response", e);
        }
    }

    @SuppressWarnings("unchecked")
    private String exchangeCodeForIdToken(String code) {
        String body = "code=" + java.net.URLEncoder.encode(code, java.nio.charset.StandardCharsets.UTF_8)
                + "&client_id=" + java.net.URLEncoder.encode(googleClientId, java.nio.charset.StandardCharsets.UTF_8)
                + "&client_secret=" + java.net.URLEncoder.encode(googleClientSecret, java.nio.charset.StandardCharsets.UTF_8)
                + "&redirect_uri=" + java.net.URLEncoder.encode(googleRedirectUri, java.nio.charset.StandardCharsets.UTF_8)
                + "&grant_type=authorization_code";
        org.springframework.http.HttpHeaders headers = new org.springframework.http.HttpHeaders();
        headers.setContentType(org.springframework.http.MediaType.APPLICATION_FORM_URLENCODED);
        org.springframework.http.HttpEntity<String> request = new org.springframework.http.HttpEntity<>(body, headers);
        ResponseEntity<String> response = restTemplate.postForEntity(GOOGLE_TOKEN_URL, request, String.class);
        if (!response.getStatusCode().is2xxSuccessful() || response.getBody() == null) {
            throw new IllegalArgumentException("Failed to exchange code for token");
        }
        try {
            JsonNode node = objectMapper.readTree(response.getBody());
            if (node.has("error")) {
                throw new IllegalArgumentException("Token exchange failed: " + node.get("error").asText());
            }
            if (!node.has("id_token")) {
                throw new IllegalArgumentException("No id_token in response");
            }
            return node.get("id_token").asText();
        } catch (Exception e) {
            if (e instanceof IllegalArgumentException) throw (IllegalArgumentException) e;
            throw new IllegalArgumentException("Invalid token response", e);
        }
    }

    public AuthResponse loginWithGoogle(String idToken) {
        JsonNode payload = verifyGoogleToken(idToken);
        String email = payload.get("email").asText();
        String name = payload.has("name") ? payload.get("name").asText() : email;
        String picture = payload.has("picture") ? payload.get("picture").asText() : null;

        User user = userMapper.selectByEmail(email);
        if (user == null) {
            user = new User();
            user.setEmail(email);
            user.setName(name);
            user.setPicture(picture);
            user.setProvider("google");
            user.setRole("user");
            user.setCreatedAt(LocalDateTime.now());
            userMapper.insert(user);
        } else {
            user.setName(name);
            user.setPicture(picture);
            userMapper.update(user);
        }

        String token = jwtUtil.generateToken(user);
        UserDto userDto = new UserDto(user.getId(), user.getEmail(), user.getName(), user.getPicture(), user.getRole());
        return new AuthResponse(token, userDto);
    }

    private JsonNode verifyGoogleToken(String idToken) {
        ResponseEntity<String> response = restTemplate.getForEntity(GOOGLE_TOKENINFO_URL + idToken, String.class);
        if (!response.getStatusCode().is2xxSuccessful() || response.getBody() == null) {
            throw new IllegalArgumentException("Invalid Google token");
        }
        try {
            JsonNode node = objectMapper.readTree(response.getBody());
            if (node.has("error")) {
                throw new IllegalArgumentException("Google token verification failed: " + node.get("error").asText());
            }
            if (googleClientId != null && !googleClientId.isEmpty() && node.has("aud")) {
                if (!Objects.equals(node.get("aud").asText(), googleClientId)) {
                    throw new IllegalArgumentException("Token audience mismatch");
                }
            }
            return node;
        } catch (Exception e) {
            if (e instanceof IllegalArgumentException) throw (IllegalArgumentException) e;
            throw new IllegalArgumentException("Invalid Google token", e);
        }
    }
}
