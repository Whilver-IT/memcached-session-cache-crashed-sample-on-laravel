'use strict'

document.addEventListener('DOMContentLoaded', dcl => {

    const readImage = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader()
            let dataUrl = null
            reader.addEventListener('load', () => {
                dataUrl = reader
                resolve(dataUrl)
            })
            reader.readAsDataURL(file)
        })
    }
    const imgfile = document.getElementById('imgfile')
    const clickEvent = async function (e) {
        try {
            imgfile.removeEventListener('change', clickEvent)
            e.preventDefault()

            if (e.target.files.length && e.target.files[0].type.substring(0, 'image'.length) == 'image') {

                const reader = await readImage(e.target.files[0])
                const img = document.createElement('img')
                img.src = reader.result
                const imgarea = document.getElementById('imgarea')
                while (imgarea.firstChild) {
                    imgarea.removeChild(imgarea.firstChild)
                }
                img.width = 200
                img.height = 200
                imgarea.appendChild(img)
                const imgdata = document.getElementById('imgdata')
                imgdata.value = reader.result

            } else {
                throw new Error('画像じゃないよ')
            }
        } catch(ex) {
            alert(ex.message)
        } finally {
            e.target.value = ''
            imgfile.addEventListener('change', clickEvent)
        }

    }
    imgfile.addEventListener('change', clickEvent)
})
