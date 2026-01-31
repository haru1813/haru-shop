package haru.park.mapper;

import haru.park.dto.CartItemDto;
import haru.park.entity.CartItem;

import java.util.List;

public interface CartItemMapper {

    List<CartItemDto> selectByUserIdWithProduct(Long userId);

    List<CartItem> selectByUserId(Long userId);

    CartItem selectById(Long id);

    int insert(CartItem cartItem);

    int updateQuantity(@org.apache.ibatis.annotations.Param("id") Long id, @org.apache.ibatis.annotations.Param("quantity") Integer quantity);

    int deleteById(Long id);

    int deleteByUserId(Long userId);
}
