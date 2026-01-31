package haru.park.mapper;

import haru.park.entity.Category;

import java.util.List;

public interface CategoryMapper {

    List<Category> selectAllOrderBySortOrderAndName();

    Category selectBySlug(String slug);
}
