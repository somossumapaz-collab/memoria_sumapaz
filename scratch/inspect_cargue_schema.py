import json

path = r'C:\Users\sotoc\Downloads\formato_Cargue.json'
with open(path, 'r', encoding='utf-8') as f:
    schema = json.load(f)

props = schema.get('properties', {})

print(f"Total top-level properties in schema: {len(props)}")
for prop_name, prop_val in props.items():
    p_type = prop_val.get('type', 'unknown')
    p_title = prop_val.get('title', '')
    p_desc = prop_val.get('description', '')
    print(f"\nProperty [{prop_name}] ({p_type}): {p_title}")
    if p_type == 'object':
        sub_props = prop_val.get('properties', {})
        print(f"   Sub-properties ({len(sub_props)}): {list(sub_props.keys())}")
    elif p_type == 'array':
        items = prop_val.get('items', {})
        item_props = items.get('properties', {})
        print(f"   Array item properties ({len(item_props)}): {list(item_props.keys())}")
