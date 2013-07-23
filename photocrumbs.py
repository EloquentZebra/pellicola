import android, time, os, ftplib

# Specify FTP connection info
server = '127.0.0.1'
username = 'username'
password = 'password'

droid = android.Android()

if not os.path.exists('/sdcard/photocrumbs/'):
	os.makedirs('/sdcard/photocrumbs/')

timestamp = time.strftime('%Y%m%d-%H%M%S', time.localtime())
droid.cameraInteractiveCapturePicture('/sdcard/photocrumbs/' + timestamp + '.jpg')

notetext = droid.dialogGetInput('Note', 'Add a note to the photocrumb:').result
note = timestamp + '.php'
file = open('/sdcard/photocrumbs/' + note, 'a')
file.write('%s\n' % (notetext))
file.close()

droid.dialogCreateAlert('Upload the photocrumb and the note?')
droid.dialogSetPositiveButtonText('Yes')
droid.dialogSetNegativeButtonText('No')
droid.dialogShow()
response = droid.dialogGetResponse().result
	
if response['which'] == 'positive':
	droid.dialogCreateSpinnerProgress('Uploading to FTP...')
	droid.dialogShow()
	conn = ftplib.FTP(server, username, password)
	file = open ('/sdcard/photocrumbs/' + timestamp + '.jpg', 'rb')
	conn.storbinary('STOR ' + timestamp + '.jpg', file)
	file.close()
	file = open ('/sdcard/photocrumbs/' + note, 'rb')
	conn.storbinary('STOR ' + note, file)
	file.close()
	conn.quit()
	droid.notify('Photocrumbs', 'Upload completed.')
	droid.dialogDismiss()
else:
		droid.notify('Photocrumbs', 'All done!')
		droid.dialogDismiss()
