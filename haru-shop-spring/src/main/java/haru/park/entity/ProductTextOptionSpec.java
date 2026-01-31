package haru.park.entity;

public class ProductTextOptionSpec {
    private Long id;
    private Long productId;
    private Long optionMasterId;
    private String label;
    private String placeholder;
    private Integer maxLength;
    private Integer sortOrder;

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public Long getProductId() { return productId; }
    public void setProductId(Long productId) { this.productId = productId; }
    public Long getOptionMasterId() { return optionMasterId; }
    public void setOptionMasterId(Long optionMasterId) { this.optionMasterId = optionMasterId; }
    public String getLabel() { return label; }
    public void setLabel(String label) { this.label = label; }
    public String getPlaceholder() { return placeholder; }
    public void setPlaceholder(String placeholder) { this.placeholder = placeholder; }
    public Integer getMaxLength() { return maxLength; }
    public void setMaxLength(Integer maxLength) { this.maxLength = maxLength; }
    public Integer getSortOrder() { return sortOrder; }
    public void setSortOrder(Integer sortOrder) { this.sortOrder = sortOrder; }
}
