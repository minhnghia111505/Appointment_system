# HƯỚNG DẪN CÀI ĐẶT VÀ CHẠY DỰ ÁN (KHÔNG DÙNG XAMPP)

Tài liệu này hướng dẫn chi tiết các cách thiết lập, cấu hình và khởi chạy dự án **Easy!Appointments** (Hệ thống đặt lịch hẹn trực tuyến) từ mã nguồn Git **hoàn toàn không cần cài đặt hoặc phụ thuộc vào XAMPP**.

---

## 📋 MỤC LỤC
1. [Tổng Quan 2 Cách Chạy Không Dùng XAMPP](#1-tổng-quan-2-cách-chạy-không-dùng-xampp)
2. [CÁCH 1: Dùng Docker & Docker Compose (Khuyên dùng - Nhanh & Tự động nhất)](#2-cách-1-dùng-docker--docker-compose-khuyên-dùng---nhanh--tự-động-nhất)
   - [Bước 1: Tải mã nguồn từ GitHub](#bước-1-tải-mã-nguồn-từ-github)
   - [Bước 2: Chuẩn bị file cấu hình `config.php`](#bước-2-chuẩn-bị-file-cấu-hình-configphp)
   - [Bước 3: Khởi động hệ thống với Docker Compose](#bước-3-khởi-động-hệ-thống-với-docker-compose)
   - [Bước 4: Cài đặt thư viện (Dependencies)](#bước-4-cài-đặt-thư-viện-dependencies)
   - [Bước 5: Khởi tạo dữ liệu & Trải nghiệm](#bước-5-khởi-tạo-dữ-liệu--trải-nghiệm)
3. [CÁCH 2: Dùng PHP CLI Độc Lập + MySQL Độc Lập](#3-cách-2-dùng-php-cli-độc-lập--mysql-độc-lập)
   - [Bước 1: Cài đặt PHP >= 8.2 (Không qua XAMPP)](#bước-1-cài-đặt-php--82-không-qua-xampp)
   - [Bước 2: Cài đặt và bật MySQL Server](#bước-2-cài-đặt-và-bật-mysql-server)
   - [Bước 3: Cấu hình `config.php`](#bước-3-cấu-hình-configphp)
   - [Bước 4: Cài đặt dependencies bằng `composer.phar`](#bước-4-cài-đặt-dependencies-bằng-composerphar)
   - [Bước 5: Khởi chạy ứng dụng](#bước-5-khởi-chạy-ứng-dụng)
   - [Bước 6: Khởi tạo cơ sở dữ liệu & Tài khoản](#bước-6-khởi-tạo-cơ-sở-dữ-liệu--tài-khoản)
4. [Các Đường Dẫn & Tài Khoản Quan Trọng](#4-các-đường-dẫn--tài-khoản-quan-trọng)
5. [Khắc Phục Lỗi Thường Gặp (Troubleshooting)](#5-khắc-phục-lỗi-thường-gặp-troubleshooting)

---

## 1. Tổng Quan 2 Cách Chạy Không Dùng XAMPP

| Tiêu chí | Cách 1: Docker & Docker Compose | Cách 2: PHP CLI + MySQL độc lập |
| :--- | :--- | :--- |
| **Độ tiện lợi** | ⭐⭐⭐⭐⭐ (Khuyên dùng) | ⭐⭐⭐⭐ |
| **Cài đặt môi trường** | Chỉ cần cài Docker Desktop | Cài riêng PHP và MySQL |
| **Cấu hình Web Server** | Tự động (Nginx + PHP-FPM tích hợp sẵn) | Dùng PHP Built-in Server tích hợp |
| **Quản trị Database** | Có sẵn container phpMyAdmin | Dùng MySQL CLI hoặc DBeaver / Navicat |
| **Phù hợp cho** | Bất kỳ máy nào (Windows, macOS, Linux) | Máy không thể cài Docker Desktop |

---

## 2. CÁCH 1: Dùng Docker & Docker Compose (Khuyên dùng - Nhanh & Tự động nhất)

Dự án đã được cấu hình sẵn môi trường container đầy đủ trong `docker-compose.yml` gồm:
- **Nginx**: Web Server (Cổng `80`)
- **PHP-FPM**: Môi trường thực thi PHP 8.2+
- **MySQL 8.0**: Cơ sở dữ liệu (Cổng `3306`)
- **phpMyAdmin**: Quản lý cơ sở dữ liệu qua web (Cổng `8080`)
- **Mailpit**: Xem thử nghiệm email (Cổng `8025`)

### Bước 1: Tải mã nguồn từ GitHub
Mở Terminal / PowerShell và tải code về máy:
```bash
git clone https://github.com/minhnghia111505/Appointment_system.git
cd Appointment_system/easyappointments
```

### Bước 2: Chuẩn bị file cấu hình `config.php`
Sao chép từ file `config-sample.php`:
```bash
cp config-sample.php config.php
```
*(Trên Windows CMD dùng lệnh: `copy config-sample.php config.php`)*

Mở file `config.php` và giữ nguyên hoặc cấu hình như sau (đã khớp sẵn với thông số container trong `docker-compose.yml`):
```php
class Config
{
    const BASE_URL = 'http://localhost';
    const LANGUAGE = 'english';
    const DEBUG_MODE = true;

    // Thông số kết nối MySQL của Docker
    const DB_HOST = 'mysql';
    const DB_NAME = 'easyappointments';
    const DB_USERNAME = 'user';
    const DB_PASSWORD = 'password';
}
```

### Bước 3: Khởi động hệ thống với Docker Compose
Đảm bảo **Docker Desktop** đang chạy, sau đó gõ lệnh:
```bash
docker compose up -d
```
Lệnh này sẽ tải và khởi động toàn bộ các container nền (Nginx, PHP, MySQL, phpMyAdmin,...).

### Bước 4: Cài đặt thư viện (Dependencies)
Chạy lệnh Composer bên trong container PHP để tải các gói thư viện cần thiết:
```bash
docker compose exec php-fpm composer install
```

### Bước 5: Khởi tạo dữ liệu & Trải nghiệm
Bạn có thể khởi tạo hệ thống theo 1 trong 2 cách:
- **Cách A (Giao diện Web)**: Mở trình duyệt truy cập [http://localhost](http://localhost), hệ thống sẽ mở màn hình cài đặt để bạn tạo tài khoản Admin và thông tin doanh nghiệp.
- **Cách B (Dòng lệnh CLI)**:
  ```bash
  docker compose exec php-fpm php index.php console install
  ```
  Lệnh sẽ tự động chạy migration tạo bảng và hiển thị mật khẩu tài khoản `administrator` trên màn hình.

> **Quản lý Database trực quan**: Truy cập [http://localhost:8080](http://localhost:8080) để vào phpMyAdmin container (Server: `mysql`, Username: `user`, Password: `password`).

---

## 3. CÁCH 2: Dùng PHP CLI Độc Lập + MySQL Độc Lập

Nếu không dùng Docker và cũng **không muốn cài XAMPP**, bạn có thể chạy ứng dụng bằng chính máy chủ tích hợp sẵn của PHP (`PHP Built-in Server`).

### Bước 1: Cài đặt PHP >= 8.2 (Không qua XAMPP)

#### Cách nhanh nhất trên Windows (dùng Winget hoặc Scoop):
Mở PowerShell (Run as Administrator) và chạy:
```powershell
winget install PHP.PHP.8.3
```
Hoặc tải bản PHP zip chính thức:
1. Truy cập [https://windows.php.net/download/](https://windows.php.net/download/) -> Tải bản **VS16 x64 Thread Safe (Zip)**.
2. Giải nén vào thư mục, ví dụ: `C:\tools\php` hoặc `D:\php`.
3. Thêm đường dẫn thư mục PHP vào biến môi trường **PATH** của Windows.
4. Trong thư mục PHP, đổi tên `php.ini-development` thành `php.ini`.
5. Mở file `php.ini` và bỏ dấu chấm phẩy `;` ở các dòng sau để kích hoạt các extension:
   ```ini
   extension=curl
   extension=fileinfo
   extension=gd
   extension=intl
   extension=mbstring
   extension=mysqli
   extension=openssl
   extension=pdo_mysql
   ```
6. Kiểm tra cài đặt thành công:
   ```bash
   php -v
   ```

---

### Bước 2: Cài đặt và bật MySQL Server

Bạn có thể chọn 1 trong 2 cách nhẹ nhàng sau để có MySQL mà không cần cài XAMPP:

- **Lựa chọn A: Dùng 1 container MySQL siêu nhẹ (khuyên dùng nếu có Docker)**:
  ```bash
  docker run -d --name ea-mysql -p 3306:3306 -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=easyappointments mysql:8.0
  ```
- **Lựa chọn B: Cài MySQL Community Server chính thức**:
  Tải và cài đặt từ trang chủ Oracle MySQL: [https://dev.mysql.com/downloads/installer/](https://dev.mysql.com/downloads/installer/).
  Sau đó mở MySQL Command Line Client hoặc công cụ bất kỳ (DBeaver, HeidiSQL, Navicat) và tạo database:
  ```sql
  CREATE DATABASE easyappointments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

---

### Bước 3: Cấu hình `config.php`

1. Tạo file `config.php` từ `config-sample.php`:
   ```bash
   copy config-sample.php config.php
   ```
2. Điền thông tin kết nối tới MySQL độc lập của bạn:
   ```php
   class Config
   {
       const BASE_URL = 'http://localhost:8000';
       const LANGUAGE = 'english';
       const DEBUG_MODE = true;

       const DB_HOST = '127.0.0.1';
       const DB_NAME = 'easyappointments';
       const DB_USERNAME = 'root';              // Tài khoản MySQL của bạn
       const DB_PASSWORD = 'mat_khau_cua_ban';  // Mật khẩu MySQL của bạn
   }
   ```

---

### Bước 4: Cài đặt dependencies bằng `composer.phar`

Dự án đã tích hợp sẵn file `composer.phar` ngay trong thư mục gốc, bạn **không cần cài đặt Composer lên máy**. Chỉ cần chạy:

```bash
php composer.phar install
```
*(Nếu máy bạn đã cài Composer toàn cục thì có thể gõ trực tiếp `composer install`)*.

---

### Bước 5: Khởi chạy ứng dụng

Chạy server bằng **1 trong 2 cách**:

- **Cách 1: Nhấp đúp vào file `start-server.bat`** trong thư mục dự án.
- **Cách 2: Gõ lệnh trực tiếp trong Terminal / CMD:**
  ```bash
  php -S 127.0.0.1:8000 router.php
  ```

Khi terminal hiển thị:
```text
PHP 8.x.x Development Server (http://127.0.0.1:8000) started
```
Máy chủ đã chạy thành công! Giữ cửa sổ terminal này trong suốt quá trình sử dụng.

---

### Bước 6: Khởi tạo cơ sở dữ liệu & Tài khoản

1. Mở trình duyệt web truy cập: [http://localhost:8000](http://localhost:8000)
2. Hệ thống sẽ tự động chuyển đến màn hình cài đặt: [http://localhost:8000/installation](http://localhost:8000/installation)
3. Điền thông tin Quản trị viên (Họ tên, Email, Username, Password) và thông tin doanh nghiệp.
4. Bấm **Install Easy!Appointments** để hoàn tất khởi tạo.

*(Hoặc mở cửa sổ terminal mới và chạy: `php index.php console install`)*.

---

## 4. Các Đường Dẫn & Tài Khoản Quan Trọng

| Chức năng | Địa chỉ truy cập (Cách 1 - Docker) | Địa chỉ truy cập (Cách 2 - PHP CLI) |
| :--- | :--- | :--- |
| **Trang Đặt Lịch (Khách hàng)** | [http://localhost](http://localhost) | [http://localhost:8000](http://localhost:8000) |
| **Trang Đăng Nhập Quản Trị (Admin)** | [http://localhost/login](http://localhost/login) | [http://localhost:8000/login](http://localhost:8000/login) |
| **Trang Cài Đặt Ban Đầu** | [http://localhost/installation](http://localhost/installation) | [http://localhost:8000/installation](http://localhost:8000/installation) |
| **Quản lý Database (phpMyAdmin)** | [http://localhost:8080](http://localhost:8080) | Dùng DBeaver / HeidiSQL |
| **Kiểm tra Email thử nghiệm (Mailpit)** | [http://localhost:8025](http://localhost:8025) | *(Nếu có bật Docker Mailpit)* |

---

## 5. Khắc Phục Lỗi Thường Gặp (Troubleshooting)

### 🔴 Lỗi 1: `php: command not found` (Không nhận diện lệnh `php`)
- **Nguyên nhân**: Bạn chưa thêm đường dẫn thư mục cài đặt PHP vào biến môi trường hệ thống `PATH`.
- **Cách khắc phục**:
  1. Nhấn phím `Windows`, gõ `env` và chọn **Edit the system environment variables**.
  2. Bấm **Environment Variables...** -> Tại mục **System variables**, chọn dòng `Path` -> Bấm **Edit**.
  3. Bấm **New** và dán đường dẫn thư mục chứa file `php.exe` vào (ví dụ `C:\tools\php`).
  4. Bấm **OK** lưu lại và tắt đi mở lại Terminal/CMD mới.

---

### 🔴 Lỗi 2: `Database connection error`
- **Nguyên nhân**: Thông tin máy chủ, tài khoản hoặc mật khẩu trong `config.php` không khớp với MySQL đang chạy.
- **Cách khắc phục**:
  - Nếu dùng **Cách 1 (Docker)**: `DB_HOST` phải là `'mysql'`, `DB_USERNAME` là `'user'`, `DB_PASSWORD` là `'password'`.
  - Nếu dùng **Cách 2 (MySQL độc lập)**: `DB_HOST` thường là `'127.0.0.1'`, kiểm tra lại username/password và đảm bảo service MySQL đang ở trạng thái Running.

---

### 🔴 Lỗi 3: Báo thiếu extension PHP (`intl`, `pdo_mysql`, `curl`,...)
- **Nguyên nhân**: Chưa bật extension tương ứng trong file `php.ini`.
- **Cách khắc phục**:
  Mở file `php.ini`, tìm dòng `extension=intl` (hoặc extension bị báo thiếu), xóa dấu chấm phẩy `;` ở đầu dòng và lưu lại.

---

### 🔴 Lỗi 4: Xung đột cổng `8000` (`Address already in use`)
- **Cách khắc phục**:
  Khởi động server trên một cổng khác chưa bị chiếm dụng (ví dụ 8080 hoặc 8888):
  ```bash
  php -S 127.0.0.1:8080 router.php
  ```
  *(Đồng thời cập nhật lại giá trị `BASE_URL` trong `config.php` thành `'http://localhost:8080'`)*.

---

*Tài liệu này được biên soạn chuẩn cho môi trường độc lập không phụ thuộc vào XAMPP.*
