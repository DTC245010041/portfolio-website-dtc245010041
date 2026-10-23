# Website Portfolio cá nhân – Đề 13

**Sinh viên:** Đỗ Tuấn Hưng  
**Mã sinh viên:** DTC245010041 - Trường Công nghệ thông tin và truyền thông
**GitHub:** https://github.com/DTC245010041/portfolio-website-dtc245010041

## 1. Kiến trúc hệ thống

- `portfolio_app`: ứng dụng PHP chạy trên Apache.
- `portfolio_mysql`: cơ sở dữ liệu MySQL.
- `portfolio_phpmyadmin`: giao diện quản trị cơ sở dữ liệu.
- `nginx_demo`: reverse proxy chuyển tiếp yêu cầu đến ứng dụng.
- `prometheus`: thu thập số liệu từ các mục tiêu được khai báo.
- `node-exporter`: cung cấp số liệu hệ thống máy chủ.
- `grafana`: hiển thị dashboard giám sát.
- `loki`: lưu trữ và truy vấn log.
- `promtail`: thu thập log Docker và chuyển đến Loki.

Các dịch vụ được triển khai bằng Docker Compose, sử dụng network và volume để kết nối dịch vụ, lưu trữ dữ liệu.

## 2. Công nghệ sử dụng

- PHP 8.3 và Apache
- MySQL 8.4
- phpMyAdmin
- Docker và Docker Compose
- Nginx
- Prometheus và Node Exporter
- Grafana
- Loki và Promtail
- Ubuntu Server
- Git và GitHub

## 3. Chức năng chính

- Hiển thị thông tin cá nhân.
- Hiển thị kỹ năng và dự án.
- Đăng nhập trang quản trị.
- Quản lý thông tin hồ sơ, kỹ năng và dự án.
- Quản lý nội dung liên quan đến tin nhắn liên hệ trong trang quản trị.
- Lưu dữ liệu trong MySQL.
- Tiếp nhận yêu cầu HTTP qua Nginx reverse proxy.
- Theo dõi số liệu máy chủ qua Prometheus và Node Exporter.
- Trực quan hóa số liệu bằng Grafana.
- Thu thập và truy vấn log bằng Loki, Promtail và LogQL.

## 4. Yêu cầu triển khai

- Ubuntu Server hoặc môi trường Linux tương thích.
- Docker Engine.
- Docker Compose plugin.
- Git.
- File cấu hình môi trường `.env` được tạo riêng trên máy triển khai.

Không đưa mật khẩu, token hoặc file `.env` chứa thông tin bí mật lên GitHub.

## 5. Cấu hình và khởi chạy

Clone repository:

```bash
git clone https://github.com/DTC245010041/portfolio-website-dtc245010041.git
cd portfolio-website-dtc245010041
```

Tạo file cấu hình môi trường:

```bash
cp .env.example .env
nano .env
chmod 600 .env
```

Cập nhật các giá trị trong `.env` bằng thông tin riêng của môi trường triển khai. Đảm bảo mật khẩu tài khoản ứng dụng MySQL trong `DB_PASS` và `MYSQL_PASSWORD` tương ứng với nhau theo cấu hình của dự án.

Kiểm tra cấu hình Docker Compose:

```bash
docker compose config
```

Khởi chạy dịch vụ:

```bash
docker compose up -d --build
docker compose ps
```

Nếu có dịch vụ chưa hoạt động, kiểm tra nhật ký:

```bash
docker compose logs --tail=100
```

**Lưu ý khi triển khai trên máy mới:** Một số network và volume được khai báo là `external` trong `compose.yaml`. Cần tạo các tài nguyên này trước khi khởi chạy hoặc điều chỉnh cấu hình cho phù hợp với môi trường mới. Không sử dụng volume dữ liệu cũ nếu chưa xác định đúng nguồn dữ liệu.

Không đưa file `.env`, mật khẩu, token hoặc dữ liệu nhạy cảm lên GitHub.

## 6. Địa chỉ truy cập

Trong môi trường Ubuntu Server thử nghiệm, máy chủ sử dụng địa chỉ IP nội bộ `192.168.79.128`.

| Thành phần | Địa chỉ |
|---|---|
| Website Portfolio qua Nginx | http://192.168.79.128:8080/ |
| Trang đăng nhập Admin | http://192.168.79.128:8080/admin/login.php |
| Grafana | http://192.168.79.128:3000/ |
| Ứng dụng PHP trực tiếp | http://127.0.0.1:8081/ |
| phpMyAdmin | http://127.0.0.1:8082/ |
| Prometheus | http://127.0.0.1:9090/ |
| Node Exporter | http://127.0.0.1:9100/ |

Các địa chỉ `127.0.0.1` chỉ truy cập trực tiếp từ máy Ubuntu. Website và Grafana được bind vào địa chỉ IP nội bộ theo cấu hình hiện tại; việc truy cập từ máy khác phụ thuộc vào kết nối mạng và quy tắc tường lửa.

Các địa chỉ trên chỉ áp dụng cho môi trường thử nghiệm. Khi triển khai ở máy khác, cần thay đổi địa chỉ IP và kiểm tra lại cấu hình cổng, network và firewall.

## 9. Kiểm tra sau triển khai

Kiểm tra trạng thái các container:

```bash
docker compose ps
```

Kiểm tra cấu hình Nginx:

```bash
docker exec nginx_demo nginx -t
```

Kiểm tra website và trang quản trị qua Nginx:

```bash
curl -I http://192.168.79.128:8080/
curl -I http://192.168.79.128:8080/admin/login.php
```

Kiểm tra nhật ký ứng dụng và cơ sở dữ liệu:

```bash
docker compose logs --tail=50 app
docker compose logs --tail=50 mysql
```

Kiểm tra các header bảo mật:

```bash
curl -sSI http://192.168.79.128:8080/
```

Đối chiếu kết quả HTTP, trạng thái container và nhật ký để xác nhận các dịch vụ hoạt động đúng.

## 7. Giám sát và nhật ký

### Prometheus

Prometheus được cấu hình để scrape chính nó và Node Exporter theo chu kỳ 15 giây.

Kiểm tra trạng thái các mục tiêu tại:

`http://127.0.0.1:9090/targets`

### Grafana

Grafana cung cấp giao diện xây dựng và xem dashboard. Đăng nhập bằng thông tin đã thiết lập trên máy chủ; không lưu mật khẩu vào repository.

### Loki và Promtail

Promtail khám phá log Docker và gửi log đến Loki.

Ví dụ truy vấn LogQL trong Grafana Explore khi đã cấu hình Loki làm data source:

```logql
{container="nginx_demo"}
```

Xem log ứng dụng:

```logql
{container="portfolio_app"}
```

Đếm số log trong 5 phút gần nhất:

```logql
count_over_time({container="nginx_demo"}[5m])
```

Phạm vi số liệu Prometheus hiện được cấu hình gồm Prometheus và Node Exporter; không mặc định hiểu rằng số liệu MySQL hay PHP đã được exporter chuyên dụng cung cấp.

## 8. Các biện pháp bảo mật

- Sử dụng biến môi trường để cấu hình mật khẩu.
- Không đưa `.env` và dữ liệu bí mật lên Git.
- Giới hạn bind các cổng ứng dụng và quản trị vào localhost khi phù hợp.
- Sử dụng UFW để giới hạn truy cập mạng.
- Nginx tắt `server_tokens`, ẩn header `X-Powered-By` từ upstream và bổ sung các header:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- Chặn truy cập các đường dẫn tệp ẩn theo cấu hình Nginx.

**Lưu ý:** cấu hình Nginx hiện tại sử dụng HTTP. HTTPS chưa được cấu hình trong phiên bản triển khai này. Các header bảo mật không thay thế cho HTTPS.

## 9. Kiểm tra sau triển khai

```bash
docker compose ps
docker compose logs --tail=50 app
docker compose logs --tail=50 mysql
curl -I http://127.0.0.1:8081/
curl -I http://192.168.79.128:8080/
```

Có thể kiểm tra cấu hình Nginx trong container:

```bash
docker exec nginx_demo nginx -t
```

## 10. Repository

Mã nguồn dự án:

https://github.com/DTC245010041/portfolio-website-dtc245010041

