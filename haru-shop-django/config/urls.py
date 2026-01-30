from django.http import HttpResponse
from django.urls import path


def home(request):
    html = """
    <!DOCTYPE html>
    <html lang="ko">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Haru Shop - Django</title>
      <style>
        body { font-family: sans-serif; margin: 2rem; background: #f5f5f5; }
        .box { max-width: 640px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        h1 { margin-top: 0; color: #222; }
        p { color: #555; line-height: 1.6; }
      </style>
    </head>
    <body>
      <div class="box">
        <h1>Haru Shop - Django</h1>
        <p>Django 백엔드가 정상 동작 중입니다.</p>
        <p><a href="/">/ (이 페이지)</a></p>
      </div>
    </body>
    </html>
    """
    return HttpResponse(html)


urlpatterns = [
    path('', home),
]
