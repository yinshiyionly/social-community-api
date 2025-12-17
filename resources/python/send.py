from kafka import KafkaProducer
import json

# 创建 KafkaProducer
producer = KafkaProducer(
    bootstrap_servers='',
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
        "status": 0,
        "is_ad": 0,
        "feature": {"asr":"","event_id":"","event_name":"","is_sensitive":False,"kg_entity_link":[{"block_infos":[{"block_risk_score":1.8417835235595703e-05,"entity_risk_scene":[],"entity_sentiment":0,"hit_mentions":["速学岛"],"text":"速学岛"}],"entity_score":1,"entity_type":"other","hit_entity":"速学岛","hit_fileds":["1"],"hit_mentions":["速学岛"],"kg_id":"","related_score":0,"risk_binary_score":1.8417835235595703e-05,"risk_scene":[],"sentiment":0}],"label_tags":[],"match_count":1,"match_id":"","ocr":"","ocr_details":[],"ocr_high":"","risk_scene":{},"sentiment":0,"tags":[]},
        "poi": {},
        "origin_id": "7365483787725081894",
        "anchor": {"filter_status": False},
        "update_category": "status_update"
    },
    "msg_id": "02171499680489100000000000000000000ffff0a96a2a5bd47f37653insight_source"
}

# 直接发送
producer.send('your_topic', msg)

# 确保发送完成
producer.flush()
