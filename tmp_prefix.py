import re

html = open('d:/laragon/www/DATN/website-to-find-workers/stitch_travel2.html', 'r', encoding='utf-8').read()

def prefix_classes(match):
    classes = match.group(1).split()
    prefixed = []
    for c in classes:
        if ':' in c:
            parts = c.split(':', 1)
            prefixed.append(f"{parts[0]}:tw-{parts[1]}")
        elif c in ['material-symbols-outlined', 'group']:
            prefixed.append(c)
        else:
            prefixed.append('tw-' + c)
    return 'class="' + ' '.join(prefixed) + '"'

new_html = re.sub(r'class="([^"]+)"', prefix_classes, html)
open('d:/laragon/www/DATN/website-to-find-workers/stitch_travel3.html', 'w', encoding='utf-8').write(new_html)
print('Done!')
