package haru.park.controller;

import haru.park.dto.WishlistItemDto;
import haru.park.entity.Wishlist;
import haru.park.mapper.WishlistMapper;
import haru.park.security.AuthPrincipal;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/wishlist")
public class WishlistController {

    private final WishlistMapper wishlistMapper;

    public WishlistController(WishlistMapper wishlistMapper) {
        this.wishlistMapper = wishlistMapper;
    }

    @GetMapping
    public ResponseEntity<List<WishlistItemDto>> list(@org.springframework.security.core.annotation.AuthenticationPrincipal AuthPrincipal principal) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        List<WishlistItemDto> list = wishlistMapper.selectByUserIdWithProduct(principal.getUserId());
        return ResponseEntity.ok(list);
    }

    @PostMapping
    public ResponseEntity<?> add(@org.springframework.security.core.annotation.AuthenticationPrincipal AuthPrincipal principal, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        Long productId = body.get("productId") != null ? Long.valueOf(body.get("productId").toString()) : null;
        if (productId == null) {
            return ResponseEntity.badRequest().body(Map.of("error", "productId required"));
        }
        Wishlist existing = wishlistMapper.selectByUserIdAndProductId(principal.getUserId(), productId);
        if (existing != null) {
            return ResponseEntity.ok(Map.of("id", existing.getId(), "ok", true));
        }
        Wishlist w = new Wishlist();
        w.setUserId(principal.getUserId());
        w.setProductId(productId);
        wishlistMapper.insert(w);
        return ResponseEntity.ok(Map.of("id", w.getId(), "ok", true));
    }

    @DeleteMapping("/{productId}")
    public ResponseEntity<?> remove(@org.springframework.security.core.annotation.AuthenticationPrincipal AuthPrincipal principal, @PathVariable Long productId) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        int deleted = wishlistMapper.deleteByUserIdAndProductId(principal.getUserId(), productId);
        if (deleted == 0) {
            return ResponseEntity.notFound().build();
        }
        return ResponseEntity.ok(Map.of("ok", true));
    }
}
