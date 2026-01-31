package haru.park.mapper;

import haru.park.dto.ReviewListItemDto;
import haru.park.entity.Review;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;

import java.util.List;

@Mapper
public interface ReviewMapper {

    List<ReviewListItemDto> selectByUserIdWithProduct(@Param("userId") Long userId);

    Review selectById(@Param("id") Long id);

    int insert(Review review);

    int deleteById(@Param("id") Long id);
}
