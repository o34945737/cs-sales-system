import re

def count_tags(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Remove script and style tags content to only check template
    content = re.sub(r'<script.*?>.*?</script>', '', content, flags=re.DOTALL)
    content = re.sub(r'<style.*?>.*?</style>', '', content, flags=re.DOTALL)
    
    # Find all tags
    tags = re.findall(r'<(/?[a-zA-Z0-9-]+).*?>', content)
    
    stack = []
    errors = []
    
    # Self-closing tags in HTML/Vue
    self_closing = {'input', 'img', 'br', 'hr', 'col', 'base', 'link', 'meta', 'Head', 'ChevronDown', 'Eye', 'Pencil', 'Trash2', 'Plus', 'Search', 'ShieldAlert', 'Users', 'Activity', 'AlertCircle', 'Upload', 'CheckCircle2', 'ArrowUpDown', 'Icon', 'AppLogoIcon', 'AppLogo'}

    for tag in tags:
        if tag.startswith('/'):
            # Closing tag
            tag_name = tag[1:]
            if not stack:
                errors.append(f"Unexpected closing tag: </{tag_name}>")
            else:
                top = stack.pop()
                if top != tag_name:
                    errors.append(f"Mismatched tags: open <{top}>, close </{tag_name}>")
        else:
            # Opening tag
            # Check if it's explicitly self-closing in the string (ends with />)
            # But re.findall(r'<(/?[a-zA-Z0-9-]+).*?>', content) doesn't capture the /> part.
            # Let's refine.
            pass

    # Better approach: count all opens and closes for common tags
    common_tags = ['div', 'section', 'article', 'aside', 'main', 'header', 'footer', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'button', 'ul', 'li', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'template', 'AppLayout', 'transition']
    
    results = {}
    for tag in common_tags:
        opens = len(re.findall(f'<{tag}[\\s>]', content))
        closes = len(re.findall(f'</{tag}>', content))
        # Account for self-closing if any (though rare for div etc)
        self_closes = len(re.findall(f'<{tag}[^>]*?/>', content))
        
        diff = opens - closes - self_closes
        if diff != 0:
            results[tag] = diff
            
    return results

print(count_tags(r'c:\laragon\www\customer_service_sales_system\cs-sales-system\resources\js\pages\Complaints\Index.vue'))
