import axios from 'axios'

export async function patchField(
  model: string,
  modelId: number,
  field: string,
  value: any,
  token: string
) {
  return axios.patch(`/api/${model}/${modelId}`, {
    [field]: value,
  }, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  })
}
