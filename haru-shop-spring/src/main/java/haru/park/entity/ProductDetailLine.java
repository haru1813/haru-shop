package haru.park.entity;

public class ProductDetailLine {
    private Long id;
    private Long productId;
    private Integer sortOrder;
    private String lineText;

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public Long getProductId() { return productId; }
    public void setProductId(Long productId) { this.productId = productId; }
    public Integer getSortOrder() { return sortOrder; }
    public void setSortOrder(Integer sortOrder) { this.sortOrder = sortOrder; }
    public String getLineText() { return lineText; }
    public void setLineText(String lineText) { this.lineText = lineText; }
}
