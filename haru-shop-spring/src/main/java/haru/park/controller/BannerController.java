package haru.park.controller;

import haru.park.entity.Banner;
import haru.park.mapper.BannerMapper;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api/banners")
public class BannerController {

    private final BannerMapper bannerMapper;

    public BannerController(BannerMapper bannerMapper) {
        this.bannerMapper = bannerMapper;
    }

    @GetMapping
    public ResponseEntity<List<Banner>> list() {
        List<Banner> list = bannerMapper.selectActiveOrderBySortOrder();
        return ResponseEntity.ok(list);
    }
}
