import re

files_to_merge_both = [
    'cinego-frontend/src/views/admin/ShowtimesView.vue'
]

def resolve_keep_both(filepath):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
    except FileNotFoundError:
        print(f"File not found: {filepath}")
        return

    pattern = re.compile(r'<<<<<<< HEAD\n(.*?)\n=======\n(.*?)\n>>>>>>> origin/cuong1\n?', re.DOTALL)
    
    new_content = pattern.sub(r'\1\n\2\n', content)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print(f"Resolved {filepath} by keeping both")

for file in files_to_merge_both:
    resolve_keep_both(file)
