package haru.park.service;

import haru.park.dto.*;
import haru.park.entity.*;
import haru.park.mapper.ProductMapper;
import org.springframework.stereotype.Service;

import java.math.BigDecimal;
import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class ProductService {

    private final ProductMapper productMapper;

    public ProductService(ProductMapper productMapper) {
        this.productMapper = productMapper;
    }

    public List<ProductListItemDto> getList(Long categoryId, String search, Integer limit, Integer offset) {
        return productMapper.selectListWithCategory(categoryId, search, limit, offset);
    }

    public ProductDetailDto getDetail(Long id) {
        Product product = productMapper.selectById(id);
        if (product == null) return null;

        ProductDetailDto dto = new ProductDetailDto();
        dto.setId(product.getId());
        dto.setName(product.getName());
        dto.setSlug(product.getSlug());
        dto.setPrice(product.getPrice());
        dto.setDescription(product.getDescription());
        dto.setImageUrl(product.getImageUrl());
        dto.setStock(product.getStock() != null ? product.getStock() : 0);

        List<ProductImage> images = productMapper.selectImagesByProductId(id);
        if (images != null && !images.isEmpty()) {
            dto.setImageUrls(images.stream().map(ProductImage::getImageUrl).collect(Collectors.toList()));
        } else if (product.getImageUrl() != null) {
            dto.setImageUrls(List.of(product.getImageUrl()));
        }

        List<OptionMaster> masters = productMapper.selectOptionMastersByProductId(id);
        if (masters != null && !masters.isEmpty()) {
            List<OptionGroupDto> groups = new ArrayList<>();
            for (OptionMaster m : masters) {
                OptionGroupDto g = new OptionGroupDto();
                g.setId(m.getId());
                g.setName(m.getName());
                g.setOptionType(m.getOptionType());
                g.setOptionKey(m.getOptionKey());
                g.setRequired(m.getIsRequired() != null && m.getIsRequired() == 1);
                g.setSortOrder(m.getSortOrder());
                List<OptionItem> items = productMapper.selectOptionItemsByOptionMasterId(m.getId());
                if (items != null) {
                    g.setItems(items.stream().map(oi -> {
                        OptionItemDto oid = new OptionItemDto();
                        oid.setId(oi.getId());
                        oid.setMasterId(oi.getOptionMasterId());
                        oid.setName(oi.getName());
                        oid.setValue(oi.getValue());
                        oid.setOptionPrice(oi.getOptionPrice() != null ? oi.getOptionPrice() : BigDecimal.ZERO);
                        oid.setSortOrder(oi.getSortOrder());
                        return oid;
                    }).collect(Collectors.toList()));
                }
                groups.add(g);
            }
            dto.setOptionGroups(groups);
        }

        List<ProductSku> skus = productMapper.selectSkusByProductId(id);
        if (skus != null && !skus.isEmpty()) {
            dto.setSkus(skus.stream().map(s -> {
                ProductSkuDto sd = new ProductSkuDto();
                sd.setId(s.getId());
                sd.setProductId(s.getProductId());
                sd.setOptionKey(s.getOptionKey());
                sd.setOptionPrice(s.getOptionPrice() != null ? s.getOptionPrice() : BigDecimal.ZERO);
                sd.setStock(s.getStock() != null ? s.getStock() : 0);
                sd.setSellStatus(s.getSellStatus());
                return sd;
            }).collect(Collectors.toList()));
        }

        List<ProductTextOptionSpec> textSpecs = productMapper.selectTextOptionSpecsByProductId(id);
        if (textSpecs != null && !textSpecs.isEmpty()) {
            dto.setTextOptionSpecs(textSpecs.stream().map(t -> {
                TextOptionSpecDto td = new TextOptionSpecDto();
                td.setMasterId(t.getOptionMasterId());
                td.setLabel(t.getLabel());
                td.setPlaceholder(t.getPlaceholder());
                td.setMaxLength(t.getMaxLength());
                return td;
            }).collect(Collectors.toList()));
        }

        List<ProductDetailLine> lines = productMapper.selectDetailLinesByProductId(id);
        if (lines != null && !lines.isEmpty()) {
            dto.setDetailDescription(lines.stream().map(ProductDetailLine::getLineText).collect(Collectors.toList()));
        }

        return dto;
    }
}
