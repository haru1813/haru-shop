package haru.park.mapper;

import haru.park.entity.Banner;

import java.util.List;

public interface BannerMapper {

    List<Banner> selectActiveOrderBySortOrder();
}
