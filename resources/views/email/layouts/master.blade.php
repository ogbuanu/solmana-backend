<body style="background-color:white">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;500&display=swap');

    * {
      box-sizing: border-box;
      font-family: "Open Sans";
    }
  </style>

  <div style="width: 100%; min-width: 480px; max-width:670px; margin: 0 auto; min-height: 300px; background-color: white; padding:32px">
    <table style="margin:24px">
      <tbody style="width: 100%;">
        <tr style="display: flex;">
          <td>
            <div>
              <a href="{{config("app.app_link")}}">
                <img 
                src='{{ $message->embed(public_path().'/logos/solmanaLogo.png') }}'
                alt="solmana logo" width="150px" />
              </a>
            </div>
          </td>
        </tr>

          @yield('content')

        <tr style="display: flex;">
          <td style="margin-top: 36px;">
            <div style="font-weight: 300; font-size:16px; ">Thanks, <br> {{config("app.name")}} team.</div>
          </td>
        </tr>
        <tr style="display: flex;">
          <td style="margin-top: 56px;">
            <div style="font-weight: 300; font-size:16px;">
              This email was sent to <span style="color: #ce00b5;">{{$to_name}}</span> because you have chosen to use {{config("app.name")}} services. If you didn't initiate this email, please reach out to support to <a style="color: #ce00b5; text-decoration:none" href="mailto:{{config("company.helpdesk")}}">unsubscribe</a> you from the mailing list.
              <br>
              <br>
              @php
               $currentYear = date('Y');
              @endphp
              © {{ $currentYear  }} {{config("app.name")}}, Lagos, Nigeria.
            </div>
          </td>
        </tr>
        <tr style="display: flex;">
          <td style="margin-top: 36px;width:100%">
            <div>
              <a href="{{config('app.app_link')}}">
                <img  
                src='{{ $message->embed(public_path().'/logos/solmanaLogo.png') }}'
                alt="solmana Logo"  width="100px" />               
              </a>
            </div>
          </td>
          <td style="margin-top: 36px;width:100%">
            <div style="font-weight: 300; font-size:16px; ; display:flex;justify-content: flex-end;gap: 20px;">
              <div style="width: 40px;">
                <a href="https://twitter.com/SolmanaLaunch">
                {{-- <img src="{{$host}}/assets/images/twitter.png" width="24px" /> --}}
                <img src='{{ $message->embed(public_path().'/logos/x.png') }}'
                alt="twitter" width="24px" />
                
              </a>
              </div>
              <div style="width: 40px;">
                <a href="https://discord.com/invite/w48G4RzJZg">
                  <img src='{{ $message->embed(public_path().'/logos/discord.png') }}'
                   alt="discord" width="24px" />
                  {{-- <img src="{{$host}}/assets/images/linkedin.png" width="24px" /> --}}
                </a>
              </div>
              <div style="width: 40px;">
                <a href="https://t.me/Solmana_launchpad">
                  <img src='{{ $message->embed(public_path().'/logos/telegram-logo.png') }}'
                   alt="telegram" width="24px" />
                {{-- <img src="{{$host}}/assets/images/instagram.png" width="24px" /> --}}
              </a>
            </div>
            <div style="width: 40px;">
              <a href="https://solmanalaunchpad.medium.com/">
               <img src='{{ $message->embed(public_path().'/logos/medium.png') }}' alt="telegram" width="24px" />
            </a>
          </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</body>

</html>