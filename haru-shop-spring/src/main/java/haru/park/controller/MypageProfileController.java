package haru.park.controller;

import haru.park.entity.User;
import haru.park.mapper.UserMapper;
import haru.park.security.AuthPrincipal;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.Map;

@RestController
@RequestMapping("/api/mypage/profile")
public class MypageProfileController {

    private final UserMapper userMapper;

    public MypageProfileController(UserMapper userMapper) {
        this.userMapper = userMapper;
    }

    @GetMapping
    public ResponseEntity<?> get(@AuthenticationPrincipal AuthPrincipal principal) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        User user = userMapper.selectById(principal.getUserId());
        if (user == null) {
            return ResponseEntity.notFound().build();
        }
        return ResponseEntity.ok(Map.of(
                "id", user.getId(),
                "email", user.getEmail() != null ? user.getEmail() : "",
                "name", user.getName() != null ? user.getName() : "",
                "picture", user.getPicture() != null ? user.getPicture() : ""
        ));
    }

    @PatchMapping
    public ResponseEntity<?> update(@AuthenticationPrincipal AuthPrincipal principal, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        User user = userMapper.selectById(principal.getUserId());
        if (user == null) {
            return ResponseEntity.notFound().build();
        }
        if (body.get("name") != null) {
            user.setName(body.get("name").toString());
        }
        if (body.get("picture") != null) {
            user.setPicture(body.get("picture").toString());
        }
        userMapper.update(user);
        return ResponseEntity.ok(Map.of("ok", true));
    }
}
