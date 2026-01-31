package haru.park.controller;

import haru.park.dto.InquiryListItemDto;
import haru.park.entity.Inquiry;
import haru.park.mapper.InquiryMapper;
import haru.park.security.AuthPrincipal;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/mypage/inquiries")
public class MypageInquiryController {

    private final InquiryMapper inquiryMapper;

    public MypageInquiryController(InquiryMapper inquiryMapper) {
        this.inquiryMapper = inquiryMapper;
    }

    @GetMapping
    public ResponseEntity<List<InquiryListItemDto>> list(@AuthenticationPrincipal AuthPrincipal principal) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        List<InquiryListItemDto> list = inquiryMapper.selectByUserIdWithProduct(principal.getUserId());
        return ResponseEntity.ok(list != null ? list : List.of());
    }

    @PostMapping
    public ResponseEntity<?> create(@AuthenticationPrincipal AuthPrincipal principal, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        Long productId = body.get("productId") != null ? Long.valueOf(body.get("productId").toString()) : null;
        String title = body.get("title") != null ? body.get("title").toString() : null;
        String content = body.get("content") != null ? body.get("content").toString() : null;
        if (productId == null || content == null || content.isBlank()) {
            return ResponseEntity.badRequest().body(Map.of("error", "productId and content required"));
        }
        Inquiry inquiry = new Inquiry();
        inquiry.setProductId(productId);
        inquiry.setUserId(principal.getUserId());
        inquiry.setTitle(title);
        inquiry.setContent(content);
        inquiryMapper.insert(inquiry);
        return ResponseEntity.ok(Map.of("id", inquiry.getId(), "ok", true));
    }
}
