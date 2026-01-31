package haru.park.mapper;

import haru.park.dto.UserCouponItemDto;
import haru.park.entity.UserCoupon;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;

import java.util.List;

@Mapper
public interface UserCouponMapper {

    List<UserCouponItemDto> selectByUserIdWithCoupon(@Param("userId") Long userId);

    UserCoupon selectByUserIdAndCouponId(@Param("userId") Long userId, @Param("couponId") Long couponId);

    int insert(UserCoupon userCoupon);
}
