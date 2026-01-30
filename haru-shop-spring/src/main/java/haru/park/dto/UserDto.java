package haru.park.dto;

public class UserDto {

    private Long id;
    private String email;
    private String name;
    private String picture;
    private String role;

    public UserDto(Long id, String email, String name, String picture, String role) {
        this.id = id;
        this.email = email;
        this.name = name;
        this.picture = picture;
        this.role = role != null ? role : "user";
    }

    public Long getId() {
        return id;
    }

    public String getEmail() {
        return email;
    }

    public String getName() {
        return name;
    }

    public String getPicture() {
        return picture;
    }

    public String getRole() {
        return role;
    }
}
