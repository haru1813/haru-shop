package haru.park.util;

import haru.park.config.JwtProperties;
import haru.park.entity.User;
import io.jsonwebtoken.Claims;
import io.jsonwebtoken.Jwts;
import io.jsonwebtoken.security.Keys;
import org.springframework.stereotype.Component;

import javax.crypto.SecretKey;
import java.nio.charset.StandardCharsets;
import java.util.Date;

@Component
public class JwtUtil {

    private final JwtProperties jwtProperties;
    private final SecretKey secretKey;

    public JwtUtil(JwtProperties jwtProperties) {
        this.jwtProperties = jwtProperties;
        byte[] keyBytes = jwtProperties.getSecret().getBytes(StandardCharsets.UTF_8);
        this.secretKey = Keys.hmacShaKeyFor(keyBytes);
    }

    public String generateToken(User user) {
        long now = System.currentTimeMillis();
        Date expiry = new Date(now + jwtProperties.getExpirationMs());
        return Jwts.builder()
                .subject(String.valueOf(user.getId()))
                .issuedAt(new Date(now))
                .expiration(expiry)
                .signWith(secretKey)
                .compact();
    }

    /** JWT에서 사용자 ID 추출. 유효하지 않으면 null */
    public Long getUserIdFromToken(String token) {
        if (token == null || token.isBlank()) {
            return null;
        }
        try {
            Claims payload = Jwts.parser()
                    .verifyWith(secretKey)
                    .build()
                    .parseSignedClaims(token)
                    .getPayload();
            String subject = payload.getSubject();
            return subject != null ? Long.valueOf(subject) : null;
        } catch (Exception e) {
            return null;
        }
    }
}
