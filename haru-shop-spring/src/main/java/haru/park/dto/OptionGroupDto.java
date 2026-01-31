package haru.park.dto;

import java.util.List;

public class OptionGroupDto {
    private Long id;
    private String name;
    private String optionType; // simple, combination, text
    private String optionKey;
    private Boolean required;
    private Integer sortOrder;
    private List<OptionItemDto> items;

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }
    public String getName() { return name; }
    public void setName(String name) { this.name = name; }
    public String getOptionType() { return optionType; }
    public void setOptionType(String optionType) { this.optionType = optionType; }
    public String getOptionKey() { return optionKey; }
    public void setOptionKey(String optionKey) { this.optionKey = optionKey; }
    public Boolean getRequired() { return required; }
    public void setRequired(Boolean required) { this.required = required; }
    public Integer getSortOrder() { return sortOrder; }
    public void setSortOrder(Integer sortOrder) { this.sortOrder = sortOrder; }
    public List<OptionItemDto> getItems() { return items; }
    public void setItems(List<OptionItemDto> items) { this.items = items; }
}
