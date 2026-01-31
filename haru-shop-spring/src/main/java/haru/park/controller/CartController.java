package haru.park.controller;

import haru.park.dto.CartItemDto;
import haru.park.entity.CartItem;
import haru.park.mapper.CartItemMapper;
import haru.park.security.AuthPrincipal;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/cart")
public class CartController {

    private final CartItemMapper cartItemMapper;

    public CartController(CartItemMapper cartItemMapper) {
        this.cartItemMapper = cartItemMapper;
    }

    private Long currentUserId() {
        var auth = SecurityContextHolder.getContext().getAuthentication();
        if (auth != null && auth.getPrincipal() instanceof AuthPrincipal) {
            return ((AuthPrincipal) auth.getPrincipal()).getUserId();
        }
        return null;
    }

    @GetMapping
    public ResponseEntity<List<CartItemDto>> list(@AuthenticationPrincipal AuthPrincipal principal) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        List<CartItemDto> list = cartItemMapper.selectByUserIdWithProduct(principal.getUserId());
        return ResponseEntity.ok(list);
    }

    @PostMapping
    public ResponseEntity<?> add(@AuthenticationPrincipal AuthPrincipal principal, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        Long productId = body.get("productId") != null ? Long.valueOf(body.get("productId").toString()) : null;
        Long skuId = body.get("skuId") != null && !"".equals(body.get("skuId").toString()) ? Long.valueOf(body.get("skuId").toString()) : null;
        Integer quantity = body.get("quantity") != null ? Integer.valueOf(body.get("quantity").toString()) : 1;
        String optionText = body.get("optionText") != null ? body.get("optionText").toString() : null;
        String selectedOptions = body.get("selectedOptions") != null ? body.get("selectedOptions").toString() : null;

        if (productId == null) {
            return ResponseEntity.badRequest().body(Map.of("error", "productId required"));
        }

        CartItem item = new CartItem();
        item.setUserId(principal.getUserId());
        item.setProductId(productId);
        item.setSkuId(skuId);
        item.setQuantity(quantity);
        item.setOptionText(optionText);
        item.setSelectedOptions(selectedOptions);
        cartItemMapper.insert(item);
        return ResponseEntity.ok(Map.of("id", item.getId(), "ok", true));
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> updateQuantity(@AuthenticationPrincipal AuthPrincipal principal, @PathVariable Long id, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        CartItem existing = cartItemMapper.selectById(id);
        if (existing == null || !existing.getUserId().equals(principal.getUserId())) {
            return ResponseEntity.notFound().build();
        }
        Integer quantity = body.get("quantity") != null ? Integer.valueOf(body.get("quantity").toString()) : null;
        if (quantity == null || quantity < 1) {
            return ResponseEntity.badRequest().body(Map.of("error", "quantity must be >= 1"));
        }
        cartItemMapper.updateQuantity(id, quantity);
        return ResponseEntity.ok(Map.of("ok", true));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> remove(@AuthenticationPrincipal AuthPrincipal principal, @PathVariable Long id) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        CartItem existing = cartItemMapper.selectById(id);
        if (existing == null || !existing.getUserId().equals(principal.getUserId())) {
            return ResponseEntity.notFound().build();
        }
        cartItemMapper.deleteById(id);
        return ResponseEntity.ok(Map.of("ok", true));
    }
}
