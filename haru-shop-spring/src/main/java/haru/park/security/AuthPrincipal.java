package haru.park.security;

import java.security.Principal;

/**
 * JWT 인증 후 SecurityContext에 담기는 principal.
 * 사용자 ID만 보관하여 컨트롤러/서비스에서 조회할 수 있게 한다.
 */
public class AuthPrincipal implements Principal {

    private final Long userId;

    public AuthPrincipal(Long userId) {
        this.userId = userId;
    }

    public Long getUserId() {
        return userId;
    }

    @Override
    public String getName() {
        return userId != null ? userId.toString() : null;
    }
}
