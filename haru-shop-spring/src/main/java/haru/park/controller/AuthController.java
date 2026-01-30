package haru.park.controller;

import haru.park.dto.AuthResponse;
import haru.park.dto.GoogleLoginRequest;
import haru.park.service.AuthService;
import jakarta.servlet.http.Cookie;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.net.URI;
import java.util.UUID;

@RestController
@RequestMapping("/api/auth")
public class AuthController {

    private static final String OAUTH_STATE_COOKIE = "oauth_state";
    private static final int COOKIE_MAX_AGE = 300;

    private final AuthService authService;

    public AuthController(AuthService authService) {
        this.authService = authService;
    }

    /** 리다이렉트 방식: Google 로그인 페이지로 302 리다이렉트 */
    @GetMapping("/google")
    public ResponseEntity<Void> googleRedirect(HttpServletResponse response) {
        String state = UUID.randomUUID().toString();
        Cookie cookie = new Cookie(OAUTH_STATE_COOKIE, state);
        cookie.setHttpOnly(true);
        cookie.setPath("/");
        cookie.setMaxAge(COOKIE_MAX_AGE);
        cookie.setSecure(false);
        response.addCookie(cookie);
        String url = authService.buildGoogleAuthUrl(state);
        return ResponseEntity.status(302).location(URI.create(url)).build();
    }

    /** 리다이렉트 방식: 카카오 로그인 페이지로 302 리다이렉트 */
    @GetMapping("/kakao")
    public ResponseEntity<Void> kakaoRedirect(HttpServletResponse response) {
        String state = UUID.randomUUID().toString();
        Cookie cookie = new Cookie(OAUTH_STATE_COOKIE, state);
        cookie.setHttpOnly(true);
        cookie.setPath("/");
        cookie.setMaxAge(COOKIE_MAX_AGE);
        cookie.setSecure(false);
        response.addCookie(cookie);
        String url = authService.buildKakaoAuthUrl(state);
        return ResponseEntity.status(302).location(URI.create(url)).build();
    }

    /** 카카오 OAuth 콜백: code 교환 후 JWT 발급, 프론트 리다이렉트 */
    @GetMapping("/kakao/callback")
    public ResponseEntity<Void> kakaoCallback(
            @RequestParam(required = false) String code,
            HttpServletResponse response
    ) {
        if (code == null || code.isBlank()) {
            return ResponseEntity.status(302).location(URI.create(authService.getFrontendRedirectUri() + "?error=no_code")).build();
        }
        try {
            AuthResponse auth = authService.loginWithKakaoCode(code);
            String userJson = new com.fasterxml.jackson.databind.ObjectMapper().writeValueAsString(auth.getUser());
            String frontUrl = authService.getFrontendRedirectUri()
                    + "?token=" + java.net.URLEncoder.encode(auth.getToken(), java.nio.charset.StandardCharsets.UTF_8)
                    + "&user=" + java.net.URLEncoder.encode(userJson, java.nio.charset.StandardCharsets.UTF_8);
            Cookie clearState = new Cookie(OAUTH_STATE_COOKIE, "");
            clearState.setPath("/");
            clearState.setMaxAge(0);
            response.addCookie(clearState);
            return ResponseEntity.status(302).location(URI.create(frontUrl)).build();
        } catch (Exception e) {
            return ResponseEntity.status(302).location(URI.create(authService.getFrontendRedirectUri() + "?error=login_failed")).build();
        }
    }

    /** 리다이렉트 방식: 네이버 로그인 페이지로 302 리다이렉트 */
    @GetMapping("/naver")
    public ResponseEntity<Void> naverRedirect(HttpServletResponse response) {
        String state = UUID.randomUUID().toString();
        Cookie cookie = new Cookie(OAUTH_STATE_COOKIE, state);
        cookie.setHttpOnly(true);
        cookie.setPath("/");
        cookie.setMaxAge(COOKIE_MAX_AGE);
        cookie.setSecure(false);
        response.addCookie(cookie);
        String url = authService.buildNaverAuthUrl(state);
        return ResponseEntity.status(302).location(URI.create(url)).build();
    }

    /** 네이버 OAuth 콜백: code 교환 후 JWT 발급, 프론트 리다이렉트 */
    @GetMapping("/naver/callback")
    public ResponseEntity<Void> naverCallback(
            @RequestParam(required = false) String code,
            HttpServletResponse response
    ) {
        if (code == null || code.isBlank()) {
            return ResponseEntity.status(302).location(URI.create(authService.getFrontendRedirectUri() + "?error=no_code")).build();
        }
        try {
            AuthResponse auth = authService.loginWithNaverCode(code);
            String userJson = new com.fasterxml.jackson.databind.ObjectMapper().writeValueAsString(auth.getUser());
            String frontUrl = authService.getFrontendRedirectUri()
                    + "?token=" + java.net.URLEncoder.encode(auth.getToken(), java.nio.charset.StandardCharsets.UTF_8)
                    + "&user=" + java.net.URLEncoder.encode(userJson, java.nio.charset.StandardCharsets.UTF_8);
            Cookie clearState = new Cookie(OAUTH_STATE_COOKIE, "");
            clearState.setPath("/");
            clearState.setMaxAge(0);
            response.addCookie(clearState);
            return ResponseEntity.status(302).location(URI.create(frontUrl)).build();
        } catch (Exception e) {
            return ResponseEntity.status(302).location(URI.create(authService.getFrontendRedirectUri() + "?error=login_failed")).build();
        }
    }

    /** Google OAuth 콜백: code 교환 후 JWT 발급, 프론트 리다이렉트 */
    @GetMapping("/google/callback")
    public ResponseEntity<Void> googleCallback(
            @RequestParam(required = false) String code,
            @CookieValue(value = OAUTH_STATE_COOKIE, required = false) String cookieState,
            HttpServletResponse response
    ) {
        if (code == null || code.isBlank()) {
            return ResponseEntity.status(302).location(URI.create(authService.getFrontendRedirectUri() + "?error=no_code")).build();
        }
        try {
            AuthResponse auth = authService.loginWithGoogleCode(code);
            String userJson = new com.fasterxml.jackson.databind.ObjectMapper().writeValueAsString(auth.getUser());
            String frontUrl = authService.getFrontendRedirectUri()
                    + "?token=" + java.net.URLEncoder.encode(auth.getToken(), java.nio.charset.StandardCharsets.UTF_8)
                    + "&user=" + java.net.URLEncoder.encode(userJson, java.nio.charset.StandardCharsets.UTF_8);
            Cookie clearState = new Cookie(OAUTH_STATE_COOKIE, "");
            clearState.setPath("/");
            clearState.setMaxAge(0);
            response.addCookie(clearState);
            return ResponseEntity.status(302).location(URI.create(frontUrl)).build();
        } catch (Exception e) {
            return ResponseEntity.status(302).location(URI.create(authService.getFrontendRedirectUri() + "?error=login_failed")).build();
        }
    }

    @PostMapping("/google")
    public ResponseEntity<AuthResponse> googleLogin(@RequestBody GoogleLoginRequest request) {
        if (request.getIdToken() == null || request.getIdToken().isBlank()) {
            return ResponseEntity.badRequest().build();
        }
        AuthResponse response = authService.loginWithGoogle(request.getIdToken());
        return ResponseEntity.ok(response);
    }
}
