package haru.park.controller;

import haru.park.dto.OrderListItemDto;
import haru.park.entity.Order;
import haru.park.security.AuthPrincipal;
import haru.park.service.OrderService;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/orders")
public class OrderController {

    private final OrderService orderService;

    public OrderController(OrderService orderService) {
        this.orderService = orderService;
    }

    @GetMapping
    public ResponseEntity<List<OrderListItemDto>> list(@AuthenticationPrincipal AuthPrincipal principal) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        List<OrderListItemDto> list = orderService.getListByUserId(principal.getUserId());
        return ResponseEntity.ok(list);
    }

    @PostMapping
    public ResponseEntity<?> create(@AuthenticationPrincipal AuthPrincipal principal, @RequestBody Map<String, Object> body) {
        if (principal == null) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).build();
        }
        try {
            Order order = orderService.createOrder(principal.getUserId(), body);
            return ResponseEntity.ok(Map.of("id", order.getId(), "orderNumber", order.getOrderNumber(), "ok", true));
        } catch (IllegalArgumentException e) {
            return ResponseEntity.badRequest().body(Map.of("error", e.getMessage()));
        }
    }
}
