package haru.park.dto;

import java.math.BigDecimal;
import java.util.List;

public class ProductDetailDto {
    private Long id;
    private String name;
    private String slug;
    private BigDecimal price;
    private String description;
    private String imageUrl;
    private Integer stock;
    private List<String> imageUrls;
    private List<OptionGroupDto> optionGroups;
    private List<ProductSkuDto> skus;
    private List<TextOptionSpecDto> textOptionSpecs;
    private List<String> detailDescription; // product_detail_lines lineText ordered

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public String getName() { return name; }
    public void setName(String name) { this.name = name; }
    public String getSlug() { return slug; }
    public void setSlug(String slug) { this.slug = slug; }
    public BigDecimal getPrice() { return price; }
    public void setPrice(BigDecimal price) { this.price = price; }
    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }
    public String getImageUrl() { return imageUrl; }
    public void setImageUrl(String imageUrl) { this.imageUrl = imageUrl; }
    public Integer getStock() { return stock; }
    public void setStock(Integer stock) { this.stock = stock; }
    public List<String> getImageUrls() { return imageUrls; }
    public void setImageUrls(List<String> imageUrls) { this.imageUrls = imageUrls; }
    public List<OptionGroupDto> getOptionGroups() { return optionGroups; }
    public void setOptionGroups(List<OptionGroupDto> optionGroups) { this.optionGroups = optionGroups; }
    public List<ProductSkuDto> getSkus() { return skus; }
    public void setSkus(List<ProductSkuDto> skus) { this.skus = skus; }
    public List<TextOptionSpecDto> getTextOptionSpecs() { return textOptionSpecs; }
    public void setTextOptionSpecs(List<TextOptionSpecDto> textOptionSpecs) { this.textOptionSpecs = textOptionSpecs; }
    public List<String> getDetailDescription() { return detailDescription; }
    public void setDetailDescription(List<String> detailDescription) { this.detailDescription = detailDescription; }
}
