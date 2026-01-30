# haru-shop-spring (Spring Boot + MariaDB client)
# Alpine 이미지는 Apple Silicon(arm64) 미지원 → Debian 기반 사용
FROM eclipse-temurin:17-jdk AS build
WORKDIR /app

COPY gradlew .
RUN chmod +x gradlew
COPY gradle gradle
COPY build.gradle settings.gradle .
RUN ./gradlew dependencies --no-daemon || true

COPY src src
RUN ./gradlew bootJar --no-daemon -x test

FROM eclipse-temurin:17-jre
WORKDIR /app
COPY --from=build /app/build/libs/*.jar app.jar
EXPOSE 8080
ENTRYPOINT ["java", "-jar", "app.jar"]
