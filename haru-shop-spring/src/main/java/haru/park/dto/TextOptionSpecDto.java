package haru.park.dto;

public class TextOptionSpecDto {
    private Long masterId;
    private String label;
    private String placeholder;
    private Integer maxLength;

    public Long getMasterId() { return masterId; }
    public void setMasterId(Long masterId) { this.masterId = masterId; }
    public String getLabel() { return label; }
    public void setLabel(String label) { this.label = label; }
    public String getPlaceholder() { return placeholder; }
    public void setPlaceholder(String placeholder) { this.placeholder = placeholder; }
    public Integer getMaxLength() { return maxLength; }
    public void setMaxLength(Integer maxLength) { this.maxLength = maxLength; }
}
