package haru.park.controller;

import haru.park.dto.UserAddressDto;
import haru.park.entity.UserAddress;
import haru.park.mapper.UserAddressMapper;
import haru.park.security.AuthPrincipal;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/mypage/addresses")
public class MypageAddressController {

    private final UserAddressMapper userAddressMapper;

    public MypageAddressController(UserAddressMapper userAddressMapper) {
        this.userAddressMapper = userAddressMapper;
    }

    @GetMapping
    public ResponseEntity<List<UserAddressDto>> list(@AuthenticationPrincipal AuthPrincipal principal) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        List<UserAddressDto> list = userAddressMapper.selectByUserId(principal.getUserId());
        return ResponseEntity.ok(list != null ? list : List.of());
    }

    @PostMapping
    public ResponseEntity<?> create(@AuthenticationPrincipal AuthPrincipal principal, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        UserAddress addr = new UserAddress();
        addr.setUserId(principal.getUserId());
        addr.setLabel(body.get("label") != null ? body.get("label").toString() : null);
        addr.setRecipientName(body.get("recipientName") != null ? body.get("recipientName").toString() : "");
        addr.setPhone(body.get("phone") != null ? body.get("phone").toString() : "");
        addr.setPostalCode(body.get("postalCode") != null ? body.get("postalCode").toString() : null);
        addr.setAddress(body.get("address") != null ? body.get("address").toString() : "");
        addr.setAddressDetail(body.get("addressDetail") != null ? body.get("addressDetail").toString() : null);
        boolean isDefault = body.get("isDefault") != null && Boolean.parseBoolean(body.get("isDefault").toString());
        addr.setIsDefault(isDefault ? 1 : 0);
        if (isDefault) {
            userAddressMapper.clearDefaultByUserId(principal.getUserId());
        }
        userAddressMapper.insert(addr);
        return ResponseEntity.ok(Map.of("id", addr.getId(), "ok", true));
    }

    @PutMapping("/{id}")
    public ResponseEntity<?> update(@AuthenticationPrincipal AuthPrincipal principal, @PathVariable Long id, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        UserAddress existing = userAddressMapper.selectById(id);
        if (existing == null || !existing.getUserId().equals(principal.getUserId())) {
            return ResponseEntity.notFound().build();
        }
        existing.setLabel(body.get("label") != null ? body.get("label").toString() : null);
        existing.setRecipientName(body.get("recipientName") != null ? body.get("recipientName").toString() : existing.getRecipientName());
        existing.setPhone(body.get("phone") != null ? body.get("phone").toString() : existing.getPhone());
        existing.setPostalCode(body.get("postalCode") != null ? body.get("postalCode").toString() : null);
        if (body.get("address") != null) existing.setAddress(body.get("address").toString());
        existing.setAddressDetail(body.get("addressDetail") != null ? body.get("addressDetail").toString() : null);
        boolean isDefault = body.get("isDefault") != null && Boolean.parseBoolean(body.get("isDefault").toString());
        existing.setIsDefault(isDefault ? 1 : 0);
        if (isDefault) {
            userAddressMapper.clearDefaultByUserId(principal.getUserId());
        }
        userAddressMapper.update(existing);
        return ResponseEntity.ok(Map.of("ok", true));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<?> delete(@AuthenticationPrincipal AuthPrincipal principal, @PathVariable Long id) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        UserAddress existing = userAddressMapper.selectById(id);
        if (existing == null || !existing.getUserId().equals(principal.getUserId())) {
            return ResponseEntity.notFound().build();
        }
        userAddressMapper.deleteById(id);
        return ResponseEntity.ok(Map.of("ok", true));
    }
}
