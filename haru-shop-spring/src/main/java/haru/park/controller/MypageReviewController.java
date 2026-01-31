package haru.park.controller;

import haru.park.dto.ReviewListItemDto;
import haru.park.entity.Review;
import haru.park.mapper.ReviewMapper;
import haru.park.security.AuthPrincipal;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/mypage/reviews")
public class MypageReviewController {

    private final ReviewMapper reviewMapper;

    public MypageReviewController(ReviewMapper reviewMapper) {
        this.reviewMapper = reviewMapper;
    }

    @GetMapping
    public ResponseEntity<List<ReviewListItemDto>> list(@AuthenticationPrincipal AuthPrincipal principal) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        List<ReviewListItemDto> list = reviewMapper.selectByUserIdWithProduct(principal.getUserId());
        return ResponseEntity.ok(list != null ? list : List.of());
    }

    @PostMapping
    public ResponseEntity<?> create(@AuthenticationPrincipal AuthPrincipal principal, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        Long productId = body.get("productId") != null ? Long.valueOf(body.get("productId").toString()) : null;
        Integer rating = body.get("rating") != null ? Integer.valueOf(body.get("rating").toString()) : 5;
        String content = body.get("content") != null ? body.get("content").toString() : null;
        if (productId == null) {
            return ResponseEntity.badRequest().body(Map.of("error", "productId required"));
        }
        Review review = new Review();
        review.setProductId(productId);
        review.setUserId(principal.getUserId());
        review.setRating(rating);
        review.setContent(content);
        reviewMapper.insert(review);
        return ResponseEntity.ok(Map.of("id", review.getId(), "ok", true));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@AuthenticationPrincipal AuthPrincipal principal, @PathVariable Long id) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        Review existing = reviewMapper.selectById(id);
        if (existing == null || !existing.getUserId().equals(principal.getUserId())) {
            return ResponseEntity.notFound().build();
        }
        reviewMapper.deleteById(id);
        return ResponseEntity.ok(Map.of("ok", true));
    }
}
