<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <script src="https://cdn.blockpass.org/widget/scripts/release/3.0.2/blockpass-kyc-connect.prod.js"></script>
  </head>

  <body>
    <button id="blockpass-kyc-connect">Connect with Blockpass</button>
    <script>
      const blockpass = new BlockpassKYCConnect("4ca88ba0-cf52-41b1-b4ab-03e650320258", {
        refId: "9bd456d9-95a0-4125-a215-c4bf02703e7a",
        email: "testing@gmail.com",
      });

      blockpass.startKYCConnect();
    </script>
  </body>
</html>
