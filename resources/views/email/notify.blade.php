@extends('email.layouts.master')
@section('content')

<tr style="display: flex;">
  <td style="margin-top: 46px;">
    <div style="font-weight: 300; font-size:16px; ">
      Hi  {{ucfirst($to_name)}},
     
      <br />
      <br />
      <span>
        {{-- {{html_entity_decode($body)}} --}}
      </span>
      <br>
      @if (!empty($link))
        <br>
        <tr style="display: flex;">
          <td style="margin-top:20px">
            <table>
              <tbody>
                <tr>
                  <td width="250px" style="margin-top: 20px;">
                    <table bgcolor="#673ab7" style="border-radius:8px;text-decoration:none;width:100%;border:solid 2px #673ab7" cellpadding="0" cellspacing="0" width="100%">
                      <tbody>
                        <tr>
                          <td class="m_7030289048136942514button-copy" style="font-family:NetflixSans-Bold,Helvetica,Roboto,Segoe UI,sans-serif;font-weight:500;font-size:18px;letter-spacing:-0.2px;color:#f5f5f1;" align="center" height="42">
                            <a href="{{$link}}" style="text-align:center;display:inline-block;text-decoration:none;color:#f5f5f1" target="_blank">Sign in</a>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
          
      @endif
    </div>
  </td>
</tr>
  
@endsection