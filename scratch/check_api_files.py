import os

api_dir = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\api'
print("Files in api/:")
for f in os.listdir(api_dir):
    print(" -", f)
