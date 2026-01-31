package haru.park.service;

import haru.park.dto.OrderItemDto;
import haru.park.dto.OrderListItemDto;
import haru.park.entity.Order;
import haru.park.entity.OrderItem;
import haru.park.mapper.OrderMapper;
import haru.park.mapper.ProductMapper;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@Service
public class OrderService {

    private final OrderMapper orderMapper;
    private final ProductMapper productMapper;

    public OrderService(OrderMapper orderMapper, ProductMapper productMapper) {
        this.orderMapper = orderMapper;
        this.productMapper = productMapper;
    }

    public List<OrderListItemDto> getListByUserId(Long userId) {
        List<OrderListItemDto> orders = orderMapper.selectByUserIdWithItems(userId);
        if (orders == null) return List.of();
        for (OrderListItemDto o : orders) {
            List<OrderItemDto> items = orderMapper.selectItemDtosByOrderId(o.getId());
            if (items != null) {
                o.setItems(items);
            }
        }
        return orders;
    }

    @Transactional
    public Order createOrder(Long userId, Map<String, Object> body) {
        @SuppressWarnings("unchecked")
        List<Map<String, Object>> items = (List<Map<String, Object>>) body.get("items");
        if (items == null || items.isEmpty()) {
            throw new IllegalArgumentException("items required");
        }

        String orderNumber = orderMapper.selectNextOrderNumber();
        if (orderNumber == null) {
            orderNumber = "ORD-" + java.time.Year.now().getValue() + "-000001";
        }

        BigDecimal totalAmount = body.get("totalAmount") != null ? new BigDecimal(body.get("totalAmount").toString()) : BigDecimal.ZERO;
        BigDecimal deliveryFee = body.get("deliveryFee") != null ? new BigDecimal(body.get("deliveryFee").toString()) : BigDecimal.ZERO;
        String receiverName = body.get("receiverName") != null ? body.get("receiverName").toString() : null;
        String receiverPhone = body.get("receiverPhone") != null ? body.get("receiverPhone").toString() : null;
        String receiverAddress = body.get("receiverAddress") != null ? body.get("receiverAddress").toString() : null;

        Order order = new Order();
        order.setUserId(userId);
        order.setOrderNumber(orderNumber);
        order.setStatus("payment_complete");
        order.setTotalAmount(totalAmount);
        order.setDeliveryFee(deliveryFee);
        order.setShippingMethod("parcel");
        order.setReceiverName(receiverName);
        order.setReceiverPhone(receiverPhone);
        order.setReceiverAddress(receiverAddress);
        orderMapper.insertOrder(order);

        for (Map<String, Object> item : items) {
            Long productId = item.get("productId") != null ? Long.valueOf(item.get("productId").toString()) : null;
            Long skuId = item.get("skuId") != null && !"".equals(item.get("skuId").toString()) ? Long.valueOf(item.get("skuId").toString()) : null;
            int quantity = item.get("quantity") != null ? Integer.valueOf(item.get("quantity").toString()) : 1;

            OrderItem oi = new OrderItem();
            oi.setOrderId(order.getId());
            oi.setProductId(productId);
            oi.setSkuId(skuId);
            oi.setProductName(item.get("productName") != null ? item.get("productName").toString() : "");
            oi.setPrice(item.get("price") != null ? new BigDecimal(item.get("price").toString()) : BigDecimal.ZERO);
            oi.setQuantity(quantity);
            oi.setOptionText(item.get("optionText") != null ? item.get("optionText").toString() : null);
            oi.setSelectedOptions(item.get("selectedOptions") != null ? item.get("selectedOptions").toString() : null);
            orderMapper.insertOrderItem(oi);

            // 주문 상품 재고 감소 (증감 연산자: SET stock = stock - quantity)
            if (skuId != null) {
                int updated = productMapper.decreaseSkuStock(skuId, quantity);
                if (updated == 0) {
                    throw new IllegalStateException("재고가 부족합니다. (SKU id: " + skuId + ")");
                }
            } else if (productId != null) {
                int updated = productMapper.decreaseProductStock(productId, quantity);
                if (updated == 0) {
                    throw new IllegalStateException("재고가 부족합니다. (상품 id: " + productId + ")");
                }
            }
        }
        return order;
    }
}
