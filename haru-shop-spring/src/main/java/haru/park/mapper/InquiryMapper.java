package haru.park.mapper;

import haru.park.dto.InquiryListItemDto;
import haru.park.entity.Inquiry;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;

import java.util.List;

@Mapper
public interface InquiryMapper {

    List<InquiryListItemDto> selectByUserIdWithProduct(@Param("userId") Long userId);

    Inquiry selectById(@Param("id") Long id);

    int insert(Inquiry inquiry);
}
