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

Tạo file `.env` từ mẫu:

```bash
cp .env.example .env
nano .env
```

Thay các giá trị mẫu bằng mật khẩu mạnh của riêng bạn. Các biến cần cấu hình gồm:

- `DB_PASS`
- `MYSQL_PASSWORD`
- `MYSQL_ROOT_PASSWORD`

Đảm bảo `DB_PASS` trùng với `MYSQL_PASSWORD` để ứng dụng sử dụng đúng tài khoản MySQL.

Khởi chạy các dịch vụ:

```bash
docker compose config
docker compose up -d --build
docker compose ps
```

Kiểm tra log nếu có dịch vụ chưa chạy:

```bash
docker compose logs --tail=100
```

Lưu ý: cấu hình network và volume bên ngoài trong `compose.yaml` phải tồn tại trên máy triển khai, hoặc cần điều chỉnh theo môi trường mới trước khi khởi chạy.

## 6. Địa chỉ truy cập trên máy triển khai hiện tại

Máy chủ thử nghiệm sử dụng IP `192.168.79.128`.

| Thành phần | Địa chỉ |
|---|---|
| Website qua Nginx | http://192.168.79.128:8080/ |
| Trang đăng nhập quản trị | http://192.168.79.128:8080/admin/login.php |
| Grafana | http://192.168.79.128:3000/ |
| Website PHP trực tiếp (chỉ localhost) | http://127.0.0.1:8081/ |
| phpMyAdmin (chỉ localhost) | http://127.0.0.1:8082/ |
| Prometheus (chỉ localhost) | http://127.0.0.1:9090/ |
| Node Exporter (chỉ localhost) | http://127.0.0.1:9100/ |

Các địa chỉ `127.0.0.1` chỉ truy cập trực tiếp được từ máy Ubuntu. Không mở các cổng quản trị này ra Internet một cách tùy tiện.

IP trên là địa chỉ của môi trường thử nghiệm hiện tại; khi triển khai nơi khác, hãy thay bằng địa chỉ máy chủ tương ứng.

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

