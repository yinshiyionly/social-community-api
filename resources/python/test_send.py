from kafka import KafkaProducer
import json
import random

# 创建 KafkaProducer
producer = KafkaProducer(
    bootstrap_servers='101.126.20.128:9092',
    value_serializer=lambda v: json.dumps(v, ensure_ascii=False).encode('utf-8')
)

# 要发送的消息
msg = {
    "item_doc": {
        "post_id": "7582133145039932729",
        "publish_time": "2024-05-05 19:58:58",
        "domain": "www.douyin.com",
        "main_domain": "douyin.com",
        "matched_task_ids": [0, 1, 2],
        "post_id": "116648754389637347",
        "post_type": 1,
        "video_info": {"cover_info":{"cover_ocr":"","cover_ocr_details":[],"online_url":"https://p6-dy-ipv6.byteimg.com/obj/tos-cn-i-0813c000-ce/osDGAExAXQSOvFZAAoefBgE9taAYC9wAypISAE"},"duration":0},
        "url": "https://www.douyin.com/share/video/7582133145039932729",
        "title": "这是测试数据的标题-速学岛",
        "based_location": {
            "public_location": {
                "city": "",
                "city_code": "",
                "city_geo_id": 0,
                "district": "",
                "district_code": "",
                "district_geo_id": 0,
                "location": "中华人民共和国北京市",
                "province": "北京市",
                "province_code": "11",
                "province_geo_id": 2038349,
                "region": "中华人民共和国",
                "region_code": "CN",
                "region_geo_id": 1814991,
                "town": "",
                "town_code": "",
                "town_geo_id": 0
            }
        },
        "status": 0,
        "is_ad": 0,
        "push_ready_time": "2025-12-10 15:49:45",
        "feature": {"asr":"这是测试数据的asr内容","event_id":"","event_name":"","is_sensitive":False,"kg_entity_link":[{"block_infos":[{"block_risk_score":1.8417835235595703e-05,"entity_risk_scene":[],"entity_sentiment":0,"hit_mentions":["速学岛"],"text":"速学岛"}],"entity_score":1,"entity_type":"other","hit_entity":"速学岛","hit_fileds":["1"],"hit_mentions":["速学岛"],"kg_id":"","related_score":0,"risk_binary_score":1.8417835235595703e-05,"risk_scene":[],"sentiment":0}],"label_tags":[],"match_count":1,"match_id":"","ocr":"这是测试数据的ocr内容","ocr_details":[],"ocr_high":"","risk_scene":{},"sentiment":0,"tags":[]},
        "poi": {},
        "origin_id": "7365483787725081894",
        "anchor": {"filter_status": False},
        "update_category": "status_update"
    },
    "msg_id": "02171499680489100000000000000000000ffff0a96a2a5bd47f37653insight_source"
}

def generate_random_id_str(num_bits=64):
    """
    生成一个指定位数的随机整数，并将其转换为字符串。
    64位整数 (约 18.4 * 10^18) 提供了足够的随机性。
    """
    # random.getrandbits(num_bits) 更加适合生成大整数 ID
    random_int = random.getrandbits(num_bits)
    return str(random_int)

## 替换 ID
### 1. 生成新的随机 ID
new_post_id = generate_random_id_str(num_bits=64)
new_origin_id = generate_random_id_str(num_bits=64)

### 2. 更新字典中的值
msg["item_doc"]["post_id"] = new_post_id
msg["item_doc"]["origin_id"] = new_origin_id

## 打印结果
print("--- 原始字典 ---")
print(json.dumps(msg, indent=4, ensure_ascii=False))

print("\n--- 替换后的 item_doc ID ---")
print(f"新的 post_id:   {msg['item_doc']['post_id']}")
print(f"新的 origin_id: {msg['item_doc']['origin_id']}")

# 直接发送
producer.send('test-topic', msg)

# 确保发送完成
producer.flush()
