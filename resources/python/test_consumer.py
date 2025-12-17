import json
import logging
import os
import time

import requests
from kafka import KafkaConsumer
from kafka.errors import KafkaError, NoBrokersAvailable

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s"
)

# ============================================================
# 配置常量 - 支持从环境变量读取
# ============================================================
# Laravel API 地址，用于接收舆情数据同步请求
API_URL = os.environ.get("INSIGHT_API_URL", "https://pre.api.opinion.infolinkx.com/api/insight/sync")
# 同步认证 Token，需与 Laravel .env 中的 INSIGHT_SYNC_TOKEN 保持一致
SYNC_TOKEN = os.environ.get("INSIGHT_SYNC_TOKEN", "GTO4mjZQXZkWYgspMWHHgla0Lf5yNew8zlgRyq")
# HTTP 请求超时时间（秒）
HTTP_TIMEOUT = int(os.environ.get("INSIGHT_HTTP_TIMEOUT", "30"))


def report_to_api(item_doc: dict, api_url: str, token: str) -> bool:
    """
    将 item_doc 数据通过 HTTP POST 上报到 Laravel API

    通过 HTTP POST 请求将 Kafka 消息中的 item_doc 数据发送到 Laravel API，
    用于持久化存储到 MySQL 数据库。

    Args:
        item_doc: Kafka 消息中的 item_doc 数据字典
        api_url: Laravel API 地址，例如 http://localhost/api/insight/sync
        token: 认证 Token，需与 Laravel .env 中的 INSIGHT_SYNC_TOKEN 一致

    Returns:
        bool: 上报是否成功
            - True: HTTP 请求成功且服务端返回 2xx 状态码
            - False: 请求失败、超时或服务端返回错误状态码

    Raises:
        不抛出异常，所有异常都会被捕获并记录日志，返回 False
    """
    # 构造请求头，包含认证 Token
    headers = {
        "Content-Type": "application/json",
        "X-Sync-Token": token
    }

    # 构造请求体
    payload = {"item_doc": item_doc}

    try:
        # 发送 POST 请求
        response = requests.post(
            api_url,
            json=payload,
            headers=headers,
            timeout=HTTP_TIMEOUT
        )

        # 检查响应状态码
        if response.status_code == 200:
            logging.info(f"[上报成功] origin_id={item_doc.get('origin_id', 'unknown')}")
            return True
        elif response.status_code == 401:
            # Token 验证失败
            logging.error(f"[上报失败] Token 验证失败，状态码: {response.status_code}，响应: {response.text}")
            return False
        elif 400 <= response.status_code < 500:
            # 客户端错误（4xx）
            logging.error(f"[上报失败] 客户端错误，状态码: {response.status_code}，响应: {response.text}")
            return False
        elif response.status_code >= 500:
            # 服务端错误（5xx）
            logging.error(f"[上报失败] 服务端错误，状态码: {response.status_code}，响应: {response.text}")
            return False
        else:
            # 其他状态码
            logging.warning(f"[上报] 未知状态码: {response.status_code}，响应: {response.text}")
            return False

    except requests.exceptions.Timeout:
        # HTTP 连接超时
        logging.error(f"[上报失败] HTTP 请求超时，origin_id={item_doc.get('origin_id', 'unknown')}")
        return False
    except requests.exceptions.ConnectionError as e:
        # 连接错误
        logging.error(f"[上报失败] HTTP 连接错误: {e}")
        return False
    except requests.exceptions.RequestException as e:
        # 其他请求异常
        logging.error(f"[上报失败] HTTP 请求异常: {e}")
        return False
    except json.JSONDecodeError as e:
        # JSON 序列化失败
        logging.error(f"[上报失败] JSON 序列化失败: {e}")
        return False
    except Exception as e:
        # 未知异常
        logging.error(f"[上报失败] 未知异常: {e}")
        return False


def create_consumer(topics, bootstrap, group_id):
    """
    创建 Kafka Consumer（封装为可重建对象）
    """
    return KafkaConsumer(
        *topics,
        bootstrap_servers=bootstrap,
        group_id=group_id,
        enable_auto_commit=False,
        auto_offset_reset="earliest",
        consumer_timeout_ms=100000,  # 100秒未收到消息会触发 StopIteration
        value_deserializer=lambda m: json.loads(m.decode("utf-8")),
        max_poll_records=100
    )


def process_message(msg) -> bool:
    """
    处理单条 Kafka 消息

    解析消息内容，过滤 post_type，对符合条件的消息调用 HTTP 上报接口。

    Args:
        msg: Kafka 消息对象，包含 value、topic、offset 等属性

    Returns:
        bool: 处理是否成功
            - True: 消息被过滤掉（不需要处理）或上报成功
            - False: 上报失败，不应提交 offset
    """
    try:
        item = msg.value.get("item_doc", {})
        post_type = item.get("post_type", None)
        post_id = item.get("post_id")

        # 不在目标列表 → 记录日志但不处理，视为成功（可提交 offset）
        if post_type not in [1, 2, 10]:
            logging.info(
                f"[过滤掉] topic={msg.topic} offset={msg.offset} "
                f"post_type={post_type} post_id={post_id}"
            )
            return True

        # 正常处理 - 符合条件的消息需要上报到 Laravel API
        logging.info(
            f"[过滤通过] topic={msg.topic} offset={msg.offset} "
            f"post_type={post_type} post_id={post_id}"
        )

        # 检查配置是否完整
        if not API_URL:
            logging.error("[配置错误] INSIGHT_API_URL 未配置")
            return False
        if not SYNC_TOKEN:
            logging.error("[配置错误] INSIGHT_SYNC_TOKEN 未配置")
            return False

        # 调用 HTTP 上报函数
        success = report_to_api(item, API_URL, SYNC_TOKEN)

        if success:
            logging.info(f"[处理完成] offset={msg.offset} 上报成功")
        else:
            logging.warning(f"[处理失败] offset={msg.offset} 上报失败，不提交 offset")

        return success

    except Exception as e:
        logging.error(f"处理消息异常: {e}")
        return False

def run_forever(topics, bootstrap, group_id):
    """
    永不退出 + 自动重连的 Kafka 消费主循环
    """

    consumer = None

    while True:  # 永久循环
        try:
            # 如果 consumer 尚未初始化，则尝试创建
            if consumer is None:
                logging.info("尝试连接 Kafka...")
                consumer = create_consumer(topics, bootstrap, group_id)
                logging.info("Kafka 连接成功，开始监听...")

            # 持续读取消息
            for msg in consumer:
                try:
                    # 处理消息，根据返回结果决定是否提交 offset
                    success = process_message(msg)
                    if success:
                        # 上报成功或消息被过滤，提交 offset
                        consumer.commit()
                        logging.debug(f"[Offset 已提交] offset={msg.offset}")
                    else:
                        # 上报失败，不提交 offset，消息将在下次消费时重试
                        logging.warning(f"[Offset 未提交] offset={msg.offset}，等待重试")
                except Exception as e:
                    logging.error(f"处理消息出错: {e}")

        except (NoBrokersAvailable, KafkaError) as e:
            logging.error(f"Kafka 连接失败或断开: {e}")
            logging.info("5 秒后重试连接...")
            time.sleep(5)
            consumer = None  # 触发重新创建 Consumer

        except StopIteration:
            # 消费超时（consumer_timeout_ms 触发）
            logging.warning("10秒内无消息，继续监听...")
            continue

        except Exception as e:
            logging.error(f"未知异常: {e}")
            logging.info("等待 5 秒后重新启动 consumer...")
            time.sleep(5)
            consumer = None  # 强制重连

        finally:
            # 仅当 consumer 对象存在且出现异常时关闭
            if consumer is not None:
                try:
                    consumer.close()
                except Exception:
                    pass
            consumer = None    # 确保下一轮重建 Consumer


if __name__ == "__main__":
    topics = ["test-topic"]
    bootstrap = "101.126.20.128:9092"
    group_id = "test-group"

    run_forever(topics, bootstrap, group_id)
