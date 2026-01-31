package haru.park.mapper;

import haru.park.dto.WishlistItemDto;
import haru.park.entity.Wishlist;

import org.apache.ibatis.annotations.Param;

import java.util.List;

public interface WishlistMapper {

    List<WishlistItemDto> selectByUserIdWithProduct(Long userId);

    List<Wishlist> selectByUserId(Long userId);

    Wishlist selectByUserIdAndProductId(Long userId, Long productId);

    int insert(Wishlist wishlist);

    int deleteByUserIdAndProductId(@Param("userId") Long userId, @Param("productId") Long productId);
}
