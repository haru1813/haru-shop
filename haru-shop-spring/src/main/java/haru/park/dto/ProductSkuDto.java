package haru.park.dto;

import java.math.BigDecimal;

public class ProductSkuDto {
    private Long id;
    private Long productId;
    private String optionKey;
    private BigDecimal optionPrice;
    private Integer stock;
    private String sellStatus;

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public Long getProductId() { return productId; }
    public void setProductId(Long productId) { this.productId = productId; }
    public String getOptionKey() { return optionKey; }
    public void setOptionKey(String optionKey) { this.optionKey = optionKey; }
    public BigDecimal getOptionPrice() { return optionPrice; }
    public void setOptionPrice(BigDecimal optionPrice) { this.optionPrice = optionPrice; }
    public Integer getStock() { return stock; }
    public void setStock(Integer stock) { this.stock = stock; }
    public String getSellStatus() { return sellStatus; }
    public void setSellStatus(String sellStatus) { this.sellStatus = sellStatus; }
}
