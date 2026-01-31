package haru.park.entity;

import java.math.BigDecimal;

public class OptionItem {
    private Long id;
    private Long optionMasterId;
    private String name;
    private String value;
    private BigDecimal optionPrice;
    private Integer sortOrder;

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public Long getOptionMasterId() { return optionMasterId; }
    public void setOptionMasterId(Long optionMasterId) { this.optionMasterId = optionMasterId; }
    public String getName() { return name; }
    public void setName(String name) { this.name = name; }
    public String getValue() { return value; }
    public void setValue(String value) { this.value = value; }
    public BigDecimal getOptionPrice() { return optionPrice; }
    public void setOptionPrice(BigDecimal optionPrice) { this.optionPrice = optionPrice; }
    public Integer getSortOrder() { return sortOrder; }
    public void setSortOrder(Integer sortOrder) { this.sortOrder = sortOrder; }
}
