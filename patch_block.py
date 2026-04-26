from pathlib import Path 
path = Path('layout.php') 
text = path.read_text() 
start = text.index('    if () {') 
end = text.index('\n\n    ', start) 
arrow = ' ' + '=' + chr(62) + ' ' 
lines = [ 
    '    if () {', 
    '         = [', 
    \" [key + arrow + home label + arrow + Dashboard href + arrow + index.php icon + arrow + bi-house-door ],"\ 
    \" [key + arrow + announcements label + arrow + Announcements href + arrow + announcements.php icon + arrow + bi-megaphone ],"\ 
    \" [key + arrow + events label + arrow + Schedules href + arrow + events.php icon + arrow + bi-calendar-event ],"\ 
    \" [key + arrow + attendance label + arrow + Attendance href + arrow + attendance.php icon + arrow + bi-qr-code-scan ],"\ 
    \" [key + arrow + settings label + arrow + Settings href + arrow + settings.php icon + arrow + bi-gear ],"\ 
    '        ];' 
    '    }' 
    '' 
lines[48:58] = lines[48:48] + new_block 
lines[48:58] = new_block 
path.write_text('\n'.join(lines) + '\n') 
