package haru.park.mapper;

import haru.park.dto.ProductListItemDto;
import haru.park.entity.*;

import java.util.List;

public interface ProductMapper {

    List<ProductListItemDto> selectListWithCategory(Long categoryId, String search, Integer limit, Integer offset);

    Product selectById(Long id);

    List<ProductImage> selectImagesByProductId(Long productId);

    List<OptionMaster> selectOptionMastersByProductId(Long productId);

    List<OptionItem> selectOptionItemsByOptionMasterId(Long optionMasterId);

    List<ProductSku> selectSkusByProductId(Long productId);

    List<ProductDetailLine> selectDetailLinesByProductId(Long productId);

    List<ProductTextOptionSpec> selectTextOptionSpecsByProductId(Long productId);

    /** 상품 재고 감소 (단독형/옵션없음). 영향받은 행 수 반환 (0이면 재고 부족) */
    int decreaseProductStock(Long productId, int quantity);

    /** SKU 재고 감소 (조합형). 영향받은 행 수 반환 (0이면 재고 부족) */
    int decreaseSkuStock(Long skuId, int quantity);
}
