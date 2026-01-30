package haru.park.mapper;

import haru.park.entity.User;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;

@Mapper
public interface UserMapper {

    User selectByEmail(@Param("email") String email);

    int insert(User user);

    int update(User user);
}
