package haru.park.controller;

import haru.park.dto.ProductDetailDto;
import haru.park.dto.ProductListItemDto;
import haru.park.service.ProductService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/products")
public class ProductController {

    private final ProductService productService;

    public ProductController(ProductService productService) {
        this.productService = productService;
    }

    /** 상품 목록 (카테고리/검색/페이징) */
    @GetMapping
    public ResponseEntity<List<ProductListItemDto>> list(
            @RequestParam(required = false) Long categoryId,
            @RequestParam(required = false) String search,
            @RequestParam(required = false, defaultValue = "20") Integer limit,
            @RequestParam(required = false, defaultValue = "0") Integer offset
    ) {
        List<ProductListItemDto> list = productService.getList(categoryId, search, limit, offset);
        return ResponseEntity.ok(list);
    }

    /** 상품 상세 (옵션/SKU/이미지/상세설명 포함) */
    @GetMapping("/{id}")
    public ResponseEntity<ProductDetailDto> getById(@PathVariable Long id) {
        ProductDetailDto dto = productService.getDetail(id);
        if (dto == null) {
            return ResponseEntity.notFound().build();
        }
        return ResponseEntity.ok(dto);
    }
}
