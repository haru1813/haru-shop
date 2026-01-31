package haru.park.mapper;

import haru.park.dto.OrderItemDto;
import haru.park.dto.OrderListItemDto;
import haru.park.entity.Order;
import haru.park.entity.OrderItem;

import java.util.List;

public interface OrderMapper {

    List<OrderListItemDto> selectByUserIdWithItems(Long userId);

    Order selectById(Long id);

    List<OrderItem> selectItemsByOrderId(Long orderId);

    List<OrderItemDto> selectItemDtosByOrderId(Long orderId);

    int insertOrder(Order order);

    int insertOrderItem(OrderItem item);

    String selectNextOrderNumber();
}
