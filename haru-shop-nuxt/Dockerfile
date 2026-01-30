# haru-shop-nuxt (Nuxt 3/4)
FROM node:20-alpine AS builder
WORKDIR /app
COPY package.json package-lock.json* .
RUN npm ci
COPY . .
RUN npm run build

FROM node:20-alpine AS run
WORKDIR /app
ENV NODE_ENV=production
ENV HOST=0.0.0.0
ENV PORT=3000
ENV NITRO_PORT=3000
COPY --from=builder /app/.output ./.output
EXPOSE 3000
CMD ["node", ".output/server/index.mjs"]
