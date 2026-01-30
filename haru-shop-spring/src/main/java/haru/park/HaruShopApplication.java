package haru.park;

import org.mybatis.spring.annotation.MapperScan;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

@SpringBootApplication
@MapperScan("haru.park.mapper")
public class HaruShopApplication {

    public static void main(String[] args) {
        SpringApplication.run(HaruShopApplication.class, args);
    }
}
