import re
text = open('pmapc.html', encoding='utf-8').read()
js = re.findall(r'<script>(.*?)</script>', text, re.DOTALL)[-1]
print('Backticks:', js.count('`'))
print('Single quotes:', js.count("'"))
print('Double quotes:', js.count('"'))
