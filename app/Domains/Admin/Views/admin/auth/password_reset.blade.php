<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重新設定密碼</title>
</head>
<body>
    <h1>重新設定密碼</h1>
    <p>您的密碼與帳號相同，請重新設定密碼以繼續使用。</p>
    @if (session('error'))
        <p style="color: red;">{{ session('error') }}</p>
    @endif
    <form action="{{ route('admin.password.reset.submit') }}" method="POST">
        @csrf
        <div>
            <label for="password">新密碼：</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div>
            <label for="password_confirmation">確認新密碼：</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required>
        </div>
        <button type="submit">提交</button>
    </form>
</body>
</html>
