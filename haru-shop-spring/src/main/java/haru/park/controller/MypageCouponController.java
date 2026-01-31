package haru.park.controller;

import haru.park.dto.UserCouponItemDto;
import haru.park.mapper.UserCouponMapper;
import haru.park.security.AuthPrincipal;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/mypage/coupons")
public class MypageCouponController {

    private final UserCouponMapper userCouponMapper;

    public MypageCouponController(UserCouponMapper userCouponMapper) {
        this.userCouponMapper = userCouponMapper;
    }

    @GetMapping
    public ResponseEntity<List<UserCouponItemDto>> list(@AuthenticationPrincipal AuthPrincipal principal) {
        if (principal == null) {
            return ResponseEntity.status(401).build();
        }
        List<UserCouponItemDto> list = userCouponMapper.selectByUserIdWithCoupon(principal.getUserId());
        return ResponseEntity.ok(list != null ? list : List.of());
    }
}
