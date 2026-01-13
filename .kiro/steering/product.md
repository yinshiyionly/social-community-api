# 产品概述

Social Community API - 基于 Laravel 的多端社区管理系统后端 API。

## 核心功能

- **System 模块**: 后台管理系统，包含用户、角色、菜单、部门、字典、日志等系统管理功能
- **App 模块**: 移动端/客户端 API，面向终端用户
- **文件上传**: 支持多存储驱动（本地、火山引擎 TOS、阿里云 OSS）

## 多端架构

| 端 | 路由前缀 | 认证方式 | 用户标识 |
|---|---------|---------|---------|
| Admin 后台 | `/api/*` | JWT (admin.auth) | user_id |
| App 客户端 | `/app/*` | JWT (app.auth) | member_id |
| System 系统 | `/api/*` | Sanctum (system.auth) | user_id |

## 业务特点

- 基于 RuoYi 风格的权限管理体系
- 支持动态菜单路由
- 统一的 API 响应格式
