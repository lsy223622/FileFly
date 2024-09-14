# FileFly

FileFly 是一个简单的文件分享服务,使用 PHP 和 MySQL 构建。它允许用户上传文件并获得一个可分享的链接。

## 功能特点

- 文件上传: 用户可以上传文件,系统会生成一个唯一的 6 位代码。
- 文件下载: 通过 6 位代码,用户可以下载之前上传的文件。
- 自动删除: 文件下载后会自动从服务器删除,确保隐私和节省空间。
- 文件大小限制: 默认限制文件大小为 10MB。
- 文件类型限制: 可以配置允许上传的文件类型。

## 安装

1. 克隆仓库:

   ```bash
   git clone https://github.com/lsy223622/FileFly.git
   ```

2. 配置数据库:
   - 创建一个新的MySQL数据库
   - 复制 `config.example.php` 为 `config.php`
   - 在 `config.php` 中填入您的数据库信息

3. 配置Web服务器:
   - 在目录下创建一个 `files` 文件夹
   - 确保PHP有权限写入 `files` 目录

4. 设置文件上传限制:
   - 在PHP配置中设置 `upload_max_filesize` 和 `post_max_size` 为适当的值(如50M)

## 使用方法

### 上传文件

发送POST请求到 `/index.php`,包含一个名为 'file' 的文件字段。

示例 (使用curl):

```bash
curl -X POST -F "file=@/path/to/your/file.txt" https://your-domain.com/index.php
```

成功时返回:

```json
{"success":true,"code":"ABC123"}
```

### 下载文件

发送GET请求到 `/index.php?code=YOUR_CODE`

示例:

```bash
https://your-domain.com/index.php?code=ABC123
```

## 配置

在 `config.php` 中,您可以修改以下设置:

- `MAX_FILE_SIZE`: 最大允许的文件大小(字节)
- `ALLOWED_FILE_EXTENSIONS`: 允许上传的文件扩展名数组

## 安全注意事项

- 此服务仅通过文件扩展名判断文件类型,不检查实际内容。
- 建议在生产环境中实施额外的安全措施,如病毒扫描。
- 定期清理未下载的旧文件以节省服务器空间。

## 贡献

欢迎提交问题和拉取请求。对于重大更改,请先开issue讨论您想要改变的内容。

## 许可

[GPL-3.0](https://www.gnu.org/licenses/gpl-3.0.html)
