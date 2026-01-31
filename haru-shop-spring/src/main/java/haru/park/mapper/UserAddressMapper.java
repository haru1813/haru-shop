package haru.park.mapper;

import haru.park.dto.UserAddressDto;
import haru.park.entity.UserAddress;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;

import java.util.List;

@Mapper
public interface UserAddressMapper {

    List<UserAddressDto> selectByUserId(@Param("userId") Long userId);

    UserAddress selectById(@Param("id") Long id);

    int insert(UserAddress address);

    int update(UserAddress address);

    int clearDefaultByUserId(@Param("userId") Long userId);

    int deleteById(@Param("id") Long id);
}
