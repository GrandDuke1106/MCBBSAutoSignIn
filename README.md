# MCBBSAutoSignIn
MCBBS自动签到脚本
****
可以在左上角切换分支.
-----------------------------
代码已经去除人机验证通过部分,需要自行补全.  
实现:
```php
function passGeeTest(...):array|bool {}
```
其中:
```php
array(
  [0] => "challenge",
  [1] => "validate result"
)
```
或者失败时返回`false`.
