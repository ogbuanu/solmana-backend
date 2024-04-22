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
  //     const blockpass = new BlockpassKYCConnect("4ca88ba0-cf52-41b1-b4ab-03e650320258", {
  //  refId: "9bd9312b-6d68-4c26-901c-6733cc019ae5",
  //       // id:"9bd4611d-9b4d-40ed-82f6-7ea20143719d"
  //        email:"typebasic@gmail.com"
  //     });

    const blockpass = new BlockpassKYCConnect("solmana_launchpad_69a8d", {
        refId: "9bd9312b-6d68-4c26-901c-6733cc019ae5",
        email: "typebasic@gmail.com"
      });

      blockpass.startKYCConnect();
    </script>
  </body>
</html>
